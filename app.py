import os
import random
import json
from datetime import datetime, date
from functools import wraps

from flask import (
    Flask, render_template, request, redirect, url_for,
    session, flash, jsonify, make_response
)
import pymysql
import pymysql.cursors
import bcrypt
from dotenv import load_dotenv

load_dotenv()

app = Flask(__name__, static_folder='static', static_url_path='/static')
app.secret_key = os.getenv('SECRET_KEY', 'mabini-inventory-secret-key-change-me')

# ─── Database ────────────────────────────────────────────────────────────────

DB_CONFIG = {
    'host': os.getenv('DB_HOST', 'localhost'),
    'user': os.getenv('DB_USER', 'root'),
    'password': os.getenv('DB_PASS', ''),
    'database': os.getenv('DB_NAME', 'mabini_inventory'),
    'charset': 'utf8mb4',
    'cursorclass': pymysql.cursors.DictCursor,
}


def get_db():
    return pymysql.connect(**DB_CONFIG)


# ─── Auth helpers ────────────────────────────────────────────────────────────

def login_required(f):
    @wraps(f)
    def decorated(*args, **kwargs):
        if not session.get('logged_in'):
            return redirect(url_for('index'))
        return f(*args, **kwargs)
    return decorated


# ─── Jinja helpers ───────────────────────────────────────────────────────────

@app.template_filter('number_format')
def number_format_filter(value, decimals=0):
    try:
        return f"{float(value):,.{decimals}f}"
    except (ValueError, TypeError):
        return value


@app.template_filter('date_format')
def date_format_filter(value, fmt='%b %d, %Y'):
    if isinstance(value, str):
        try:
            value = datetime.strptime(value, '%Y-%m-%d')
        except ValueError:
            return value
    if isinstance(value, (datetime, date)):
        return value.strftime(fmt)
    return value


@app.context_processor
def inject_now():
    return {'now': datetime.now(), 'today': date.today()}


# ─── Routes ──────────────────────────────────────────────────────────────────

# ---------- Login / Logout ----------

@app.route('/')
@app.route('/index.php')
def index():
    return render_template('index.html')


@app.route('/login_process.php', methods=['POST'])
@app.route('/login', methods=['POST'])
def login_process():
    username = request.form.get('email', '').strip()
    password = request.form.get('password', '').strip()

    if not username or not password:
        flash('Please enter both username and password.', 'error')
        session['old_email'] = username
        return redirect(url_for('index'))

    try:
        conn = get_db()
        with conn.cursor() as cur:
            cur.execute(
                "SELECT id, username, password, full_name, email, role, status "
                "FROM users WHERE (username=%s OR email=%s) AND status='Active'",
                (username, username),
            )
            user = cur.fetchone()
        conn.close()

        if user and bcrypt.checkpw(password.encode(), user['password'].encode()):
            session['user_id'] = user['id']
            session['username'] = user['username']
            session['user'] = user['full_name'] or user['username']
            session['email'] = user['email'] or ''
            session['role'] = user['role']
            session['logged_in'] = True

            if request.form.get('remember'):
                resp = make_response(redirect(url_for('dashboard')))
                resp.set_cookie('remember_email', username, max_age=30*86400)
                return resp

            return redirect(url_for('dashboard'))

        flash('Invalid username or password.', 'error')
        session['old_email'] = username
        return redirect(url_for('index'))

    except Exception:
        flash('Database error. Please try again later.', 'error')
        session['old_email'] = username
        return redirect(url_for('index'))


@app.route('/logout.php', methods=['GET', 'POST'])
@app.route('/logout', methods=['GET', 'POST'])
def logout():
    session.clear()
    flash('You have been successfully logged out.', 'success')
    return redirect(url_for('index'))


# ---------- Dashboard ----------

@app.route('/dashboard.php')
@app.route('/dashboard')
@login_required
def dashboard():
    # Session-based stats (legacy pages)
    products = session.get('products', [])
    costs = session.get('costs', [])
    quantities = session.get('quantities', [])
    issued = session.get('issued', [])

    total_products = len(products)
    total_quantity = sum(float(q.get('quantity', 0)) for q in quantities)
    total_issued = sum(float(i.get('quantity_issued', i.get('quantity', 0))) for i in issued)
    total_value = sum(float(c.get('unit_cost', 0)) for c in costs)

    return render_template('dashboard.html',
                           total_products=total_products,
                           total_quantity=total_quantity,
                           total_issued=total_issued,
                           total_value=total_value)


# ---------- Item Master List ----------

@app.route('/item-master-list.php', methods=['GET', 'POST'])
@app.route('/item-master-list', methods=['GET', 'POST'])
@login_required
def item_master_list():
    conn = get_db()

    # AJAX item details
    if request.args.get('action') == 'get_item_details' and request.args.get('id'):
        item_id = int(request.args['id'])
        with conn.cursor() as cur:
            cur.execute(
                "SELECT i.*, c.category_name FROM items i "
                "LEFT JOIN categories c ON i.category_id=c.id WHERE i.id=%s",
                (item_id,),
            )
            item = cur.fetchone()
            if not item:
                conn.close()
                return jsonify(error='Item not found')
            # Convert date/datetime values to strings for JSON
            for k, v in item.items():
                if isinstance(v, (datetime, date)):
                    item[k] = v.isoformat()

            cur.execute(
                "SELECT DISTINCT o.office_name, it.transaction_date, it.quantity, it.status "
                "FROM inventory_transactions it JOIN offices o ON it.office_id=o.id "
                "WHERE it.item_id=%s AND it.transaction_type='Issue' "
                "ORDER BY it.transaction_date DESC LIMIT 5",
                (item_id,),
            )
            agencies = cur.fetchall()
            for a in agencies:
                for k, v in a.items():
                    if isinstance(v, (datetime, date)):
                        a[k] = v.isoformat()
        conn.close()
        return jsonify(item=item, agencies=agencies)

    # POST actions
    if request.method == 'POST':
        action = request.form.get('action', '')

        if action == 'add_item':
            exp = request.form.get('expiration_date') or None
            with conn.cursor() as cur:
                cur.execute(
                    "INSERT INTO items (item_code,item_name,category_id,unit,unit_cost,"
                    "quantity_on_hand,reorder_level,expiration_date,status,created_by) "
                    "VALUES (%s,%s,%s,%s,%s,%s,%s,%s,'Active',%s)",
                    (
                        request.form.get('item_code', ''),
                        request.form.get('item_name', ''),
                        request.form.get('category') or None,
                        request.form.get('unit', 'piece'),
                        request.form.get('unit_cost', 0),
                        request.form.get('quantity_on_hand', 0),
                        request.form.get('reorder_level', 10),
                        exp,
                        session.get('user_id'),
                    ),
                )
            conn.commit()
            flash('Item added successfully!', 'success')

        elif action == 'update_item':
            exp = request.form.get('expiration_date') or None
            with conn.cursor() as cur:
                cur.execute(
                    "UPDATE items SET item_code=%s,item_name=%s,category_id=%s,unit=%s,"
                    "unit_cost=%s,quantity_on_hand=%s,reorder_level=%s,expiration_date=%s,"
                    "status=%s,updated_at=NOW() WHERE id=%s",
                    (
                        request.form.get('item_code', ''),
                        request.form.get('item_name', ''),
                        request.form.get('category') or None,
                        request.form.get('unit', 'piece'),
                        request.form.get('unit_cost', 0),
                        request.form.get('quantity_on_hand', 0),
                        request.form.get('reorder_level', 10),
                        exp,
                        request.form.get('status', 'Active'),
                        request.form.get('id', 0),
                    ),
                )
            conn.commit()
            flash('Item updated successfully!', 'success')

        elif action == 'delete_item':
            with conn.cursor() as cur:
                cur.execute("DELETE FROM items WHERE id=%s", (request.form.get('id', 0),))
            conn.commit()
            flash('Item deleted successfully!', 'success')

        elif action == 'upload_excel':
            f = request.files.get('excel_file')
            if f:
                import openpyxl
                from io import BytesIO
                wb = openpyxl.load_workbook(BytesIO(f.read()))
                ws = wb.active
                rows = list(ws.iter_rows(min_row=2, values_only=True))
                imported = 0
                with conn.cursor() as cur:
                    for row in rows:
                        if not row[0] and not row[1]:
                            continue
                        cur.execute(
                            "INSERT INTO items (item_code,item_name,category_id,unit,unit_cost,"
                            "quantity_on_hand,reorder_level,status,created_by) "
                            "VALUES (%s,%s,%s,%s,%s,%s,%s,'Active',%s)",
                            (
                                row[0] or '', row[1] or '', row[3] if len(row) > 3 else None,
                                row[4] if len(row) > 4 else 'piece',
                                row[5] if len(row) > 5 else 0,
                                row[6] if len(row) > 6 else 0,
                                row[7] if len(row) > 7 else 10,
                                session.get('user_id'),
                            ),
                        )
                        imported += 1
                conn.commit()
                flash(f'Successfully imported {imported} items!', 'success')

        conn.close()
        return redirect(url_for('item_master_list'))

    # GET
    with conn.cursor() as cur:
        cur.execute(
            "SELECT i.*, c.category_name FROM items i "
            "LEFT JOIN categories c ON i.category_id=c.id ORDER BY i.item_code ASC"
        )
        items = cur.fetchall()
        cur.execute("SELECT id, category_name FROM categories WHERE status='Active' ORDER BY category_name")
        categories = cur.fetchall()
    conn.close()

    total_items = len(items)
    low_stock = 0
    total_value = 0.0
    for it in items:
        qty = float(it.get('quantity_on_hand') or 0)
        reorder = float(it.get('reorder_level') or 0)
        cost = float(it.get('unit_cost') or 0)
        if qty <= reorder and reorder > 0:
            low_stock += 1
        total_value += qty * cost

    return render_template('item_master_list.html',
                           items=items, categories=categories,
                           total_items=total_items, low_stock=low_stock,
                           total_value=total_value)


# ---------- Issue Items ----------

@app.route('/issue-items.php', methods=['GET', 'POST'])
@app.route('/issue-items', methods=['GET', 'POST'])
@login_required
def issue_items():
    conn = get_db()

    if request.method == 'POST' and request.form.get('action') == 'create_issue':
        try:
            item_id = request.form['item_id']
            office_id = request.form['office_id']
            quantity = int(request.form['quantity'])
            remarks = request.form.get('remarks', '')

            with conn.cursor() as cur:
                cur.execute("SELECT item_code,item_name,quantity_on_hand,unit_cost FROM items WHERE id=%s", (item_id,))
                item = cur.fetchone()
                if not item:
                    raise Exception('Item not found')
                if item['quantity_on_hand'] < quantity:
                    raise Exception(f"Insufficient stock. Available: {item['quantity_on_hand']}")

                ref = f"ISS-{datetime.now():%Y%m%d}-{random.randint(1,9999):04d}"
                total_cost = quantity * float(item['unit_cost'])
                cur.execute(
                    "INSERT INTO inventory_transactions "
                    "(transaction_type,transaction_date,reference_number,office_id,item_id,"
                    "quantity,unit_cost,total_cost,remarks,created_by,status) "
                    "VALUES ('Issue',CURDATE(),%s,%s,%s,%s,%s,%s,%s,%s,'Pending')",
                    (ref, office_id, item_id, quantity, item['unit_cost'], total_cost, remarks, session.get('user_id', 1)),
                )
            conn.commit()
            flash(f"Issue request created successfully! Reference: {ref}", 'success')
        except Exception as e:
            conn.rollback()
            flash(f"Error: {e}", 'error')
        conn.close()
        return redirect(url_for('issue_items'))

    with conn.cursor() as cur:
        cur.execute(
            "SELECT it.*,o.office_name,i.item_name,i.item_code,u.full_name as processed_by_name "
            "FROM inventory_transactions it "
            "LEFT JOIN offices o ON it.office_id=o.id "
            "LEFT JOIN items i ON it.item_id=i.id "
            "LEFT JOIN users u ON it.processed_by=u.id "
            "WHERE it.transaction_type='Issue' ORDER BY it.created_at DESC LIMIT 50"
        )
        transactions = cur.fetchall()
        cur.execute("SELECT id,office_name FROM offices WHERE status='Active' ORDER BY office_name")
        offices = cur.fetchall()
        cur.execute("SELECT id,item_code,item_name,quantity_on_hand,unit FROM items WHERE status='Active' ORDER BY item_name")
        items = cur.fetchall()
    conn.close()

    today_str = date.today().isoformat()
    pending_count = sum(1 for t in transactions if t['status'] == 'Pending')
    completed_today = sum(1 for t in transactions if t['status'] == 'Completed' and str(t.get('transaction_date', '')) == today_str)

    return render_template('issue_items.html', transactions=transactions, offices=offices, items=items,
                           pending_count=pending_count, completed_today=completed_today)


# ---------- Receive Items ----------

@app.route('/receive-items.php', methods=['GET', 'POST'])
@app.route('/receive-items', methods=['GET', 'POST'])
@login_required
def receive_items():
    conn = get_db()

    if request.method == 'POST' and request.form.get('action') == 'receive_items':
        try:
            item_id = request.form['item_id']
            quantity = int(request.form['quantity'])
            unit_cost = float(request.form['unit_cost'])
            supplier_id = request.form.get('supplier_id') or None
            remarks = request.form.get('remarks', '')

            with conn.cursor() as cur:
                cur.execute("SELECT item_code,item_name,quantity_on_hand FROM items WHERE id=%s", (item_id,))
                item = cur.fetchone()
                if not item:
                    raise Exception('Item not found')

                ref = f"RCV-{datetime.now():%Y%m%d}-{random.randint(1,9999):04d}"
                total_cost = quantity * unit_cost
                user_id = session.get('user_id', 1)
                cur.execute(
                    "INSERT INTO inventory_transactions "
                    "(transaction_type,transaction_date,reference_number,item_id,"
                    "quantity,unit_cost,total_cost,remarks,created_by,processed_by,status) "
                    "VALUES ('Receive',CURDATE(),%s,%s,%s,%s,%s,%s,%s,%s,'Completed')",
                    (ref, item_id, quantity, unit_cost, total_cost, remarks, user_id, user_id),
                )
                new_qty = item['quantity_on_hand'] + quantity
                cur.execute(
                    "UPDATE items SET quantity_on_hand=%s,unit_cost=%s,"
                    "supplier_id=COALESCE(%s,supplier_id),updated_at=NOW() WHERE id=%s",
                    (new_qty, unit_cost, supplier_id, item_id),
                )
                cur.execute(
                    "INSERT INTO stock_movements "
                    "(item_id,transaction_id,movement_type,quantity,balance_after,reference,remarks,created_by) "
                    "VALUES (%s,LAST_INSERT_ID(),'IN',%s,%s,%s,%s,%s)",
                    (item_id, quantity, new_qty, ref, remarks, user_id),
                )
            conn.commit()
            flash(f"Items received successfully! Reference: {ref}", 'success')
        except Exception as e:
            conn.rollback()
            flash(f"Error: {e}", 'error')
        conn.close()
        return redirect(url_for('receive_items'))

    with conn.cursor() as cur:
        cur.execute(
            "SELECT it.*,i.item_name,i.item_code,u.full_name as processed_by_name "
            "FROM inventory_transactions it LEFT JOIN items i ON it.item_id=i.id "
            "LEFT JOIN users u ON it.processed_by=u.id "
            "WHERE it.transaction_type='Receive' ORDER BY it.created_at DESC LIMIT 50"
        )
        receipts = cur.fetchall()
        cur.execute("SELECT id,item_code,item_name,unit_cost,unit FROM items WHERE status='Active' ORDER BY item_name")
        items = cur.fetchall()
        cur.execute("SELECT id,supplier_name FROM suppliers WHERE status='Active' ORDER BY supplier_name")
        suppliers = cur.fetchall()
    conn.close()

    today_str = date.today().isoformat()
    received_today = sum(1 for r in receipts if str(r.get('transaction_date', '')) == today_str)
    received_month = sum(1 for r in receipts if str(r.get('transaction_date', ''))[:7] == datetime.now().strftime('%Y-%m'))
    total_value = sum(float(r.get('total_cost', 0) or 0) for r in receipts)

    return render_template('receive_items.html', receipts=receipts, items=items, suppliers=suppliers,
                           received_today=received_today, received_month=received_month, total_value=total_value)


# ---------- Process Transactions ----------

@app.route('/process-transactions.php', methods=['GET', 'POST'])
@app.route('/process-transactions', methods=['GET', 'POST'])
@login_required
def process_transactions():
    conn = get_db()

    if request.method == 'POST' and request.form.get('action'):
        action = request.form['action']
        tid = request.form['transaction_id']
        user_id = session.get('user_id', 1)

        try:
            with conn.cursor() as cur:
                if action == 'approve':
                    cur.execute("UPDATE inventory_transactions SET status='Approved',processed_by=%s,updated_at=NOW() WHERE id=%s", (user_id, tid))
                    flash('Transaction approved successfully!', 'success')

                elif action == 'complete':
                    cur.execute("SELECT item_id,quantity,transaction_type FROM inventory_transactions WHERE id=%s", (tid,))
                    trans = cur.fetchone()
                    if trans and trans['transaction_type'] == 'Issue':
                        cur.execute(
                            "UPDATE items SET quantity_on_hand=quantity_on_hand-%s,updated_at=NOW() "
                            "WHERE id=%s AND quantity_on_hand>=%s",
                            (trans['quantity'], trans['item_id'], trans['quantity']),
                        )
                        if cur.rowcount == 0:
                            raise Exception('Insufficient stock to complete this transaction')
                        cur.execute("SELECT quantity_on_hand FROM items WHERE id=%s", (trans['item_id'],))
                        new_bal = cur.fetchone()['quantity_on_hand']
                        cur.execute(
                            "INSERT INTO stock_movements (item_id,transaction_id,movement_type,quantity,balance_after,created_by) "
                            "VALUES (%s,%s,'OUT',%s,%s,%s)",
                            (trans['item_id'], tid, trans['quantity'], new_bal, user_id),
                        )
                    cur.execute("UPDATE inventory_transactions SET status='Completed',processed_by=%s,updated_at=NOW() WHERE id=%s", (user_id, tid))
                    flash('Transaction completed successfully!', 'success')

                elif action == 'cancel':
                    cur.execute("UPDATE inventory_transactions SET status='Cancelled',processed_by=%s,updated_at=NOW() WHERE id=%s", (user_id, tid))
                    flash('Transaction cancelled', 'success')

            conn.commit()
        except Exception as e:
            conn.rollback()
            flash(f"Error: {e}", 'error')
        conn.close()
        return redirect(url_for('process_transactions'))

    status_filter = request.args.get('status', 'all')
    type_filter = request.args.get('type', 'all')

    query = (
        "SELECT it.*,o.office_name,i.item_name,i.item_code,i.quantity_on_hand,"
        "u.full_name as processed_by_name FROM inventory_transactions it "
        "LEFT JOIN offices o ON it.office_id=o.id "
        "LEFT JOIN items i ON it.item_id=i.id "
        "LEFT JOIN users u ON it.processed_by=u.id WHERE 1=1 "
    )
    params = []
    if status_filter != 'all':
        query += " AND it.status=%s"
        params.append(status_filter)
    if type_filter != 'all':
        query += " AND it.transaction_type=%s"
        params.append(type_filter)
    query += (
        " ORDER BY CASE it.status WHEN 'Pending' THEN 1 WHEN 'Approved' THEN 2 "
        "WHEN 'Completed' THEN 3 ELSE 4 END, it.created_at DESC LIMIT 100"
    )

    with conn.cursor() as cur:
        cur.execute(query, params)
        transactions = cur.fetchall()
    conn.close()

    today_str = date.today().isoformat()
    pending_count = sum(1 for t in transactions if t['status'] == 'Pending')
    approved_count = sum(1 for t in transactions if t['status'] == 'Approved')
    completed_today = sum(1 for t in transactions if t['status'] == 'Completed' and str(t.get('updated_at', ''))[:10] == today_str)

    return render_template('process_transactions.html',
                           transactions=transactions,
                           status_filter=status_filter,
                           type_filter=type_filter,
                           pending_count=pending_count,
                           approved_count=approved_count,
                           completed_today=completed_today)


# ---------- Offices ----------

@app.route('/offices.php', methods=['GET', 'POST'])
@app.route('/offices', methods=['GET', 'POST'])
@login_required
def offices():
    # Session-based offices (same as PHP version)
    if 'offices' not in session:
        session['offices'] = [
            {'id': i+1, 'office_name': n, 'created_at': datetime.now().strftime('%Y-%m-%d %H:%M:%S')}
            for i, n in enumerate([
                'M.O','V.M.O','HRMO','MPDC','LCR','MBO','ACCOUNTING','MTO','ASSESSOR',
                'LIBRARY','RHU','MSWD','AGRI','ENGINEERING','MARKET','MDR','R.S.I',
                'DENTAL','M.I','NUTRITION','MOTORPOOL','DILG','OSCA','BAWASA','BPLO',
                'MIDWIFE','LEGAL OFFICE','GSO',
            ])
        ]

    if request.method == 'POST':
        action = request.form.get('action', '')
        if action == 'add_office':
            name = request.form.get('office_name', '').strip()
            if name:
                offices_list = session['offices']
                max_id = max((o['id'] for o in offices_list), default=0)
                offices_list.append({'id': max_id+1, 'office_name': name, 'created_at': datetime.now().strftime('%Y-%m-%d %H:%M:%S')})
                session['offices'] = offices_list
            flash('Office added successfully!', 'success')
        elif action == 'edit_office':
            oid = int(request.form.get('id', 0))
            new_name = request.form.get('office_name', '').strip()
            if new_name:
                offices_list = session['offices']
                for o in offices_list:
                    if o['id'] == oid:
                        o['office_name'] = new_name
                        break
                session['offices'] = offices_list
            flash('Office updated successfully!', 'success')
        elif action == 'delete_office':
            oid = int(request.form.get('id', 0))
            session['offices'] = [o for o in session['offices'] if o['id'] != oid]
            flash('Office deleted successfully!', 'success')
        return redirect(url_for('offices'))

    return render_template('offices.html', offices=session['offices'])


# ---------- Report ----------

@app.route('/report.php')
@app.route('/report')
@login_required
def report():
    products = session.get('products', [])
    offices_list = session.get('offices', [])
    issued = session.get('issued', [])

    total_items = len(products)
    total_offices = len(offices_list)
    total_issued = sum(float(i.get('quantity_issued', i.get('quantity', 0))) for i in issued)
    total_value = sum(float(p.get('price', 0)) for p in products)

    return render_template('report.html',
                           products=products, total_items=total_items,
                           total_offices=total_offices, total_issued=total_issued,
                           total_value=total_value)


# ---------- Reorder Management ----------

@app.route('/reorder-management.php')
@app.route('/reorder-management')
@login_required
def reorder_management():
    conn = get_db()
    with conn.cursor() as cur:
        cur.execute(
            "SELECT i.*,(i.reorder_level - i.quantity_on_hand) as shortage,"
            "(i.reorder_level * 2) as suggested_order_qty "
            "FROM items i WHERE i.status='Active' AND i.quantity_on_hand<=i.reorder_level "
            "ORDER BY CASE WHEN i.quantity_on_hand=0 THEN 1 "
            "WHEN i.quantity_on_hand<i.reorder_level THEN 2 ELSE 3 END, i.quantity_on_hand ASC"
        )
        low_stock_items = cur.fetchall()
    conn.close()

    out_of_stock = sum(1 for i in low_stock_items if i['quantity_on_hand'] == 0)
    critical = sum(1 for i in low_stock_items if i['quantity_on_hand'] > 0 and i['quantity_on_hand'] < (i['reorder_level'] / 2))

    return render_template('reorder_management.html',
                           low_stock_items=low_stock_items,
                           total_low_stock=len(low_stock_items),
                           out_of_stock=out_of_stock,
                           critical_stock=critical)


# ---------- Products (session-based) ----------

@app.route('/products.php', methods=['GET', 'POST'])
@app.route('/products', methods=['GET', 'POST'])
@login_required
def products():
    if 'products' not in session:
        session['products'] = []

    if request.method == 'POST':
        action = request.form.get('action', '')
        if action == 'add_product':
            prods = session['products']
            prods.append({
                'id': len(prods) + 1,
                'name': request.form.get('product_name', ''),
                'description': request.form.get('description', ''),
                'category': request.form.get('category', ''),
                'price': request.form.get('price', 0),
                'date_issued': request.form.get('date_issued', date.today().isoformat()),
                'created_at': datetime.now().strftime('%Y-%m-%d %H:%M:%S'),
            })
            session['products'] = prods
            flash('Product added successfully!', 'success')
        elif action == 'delete_product':
            pid = int(request.form.get('id', 0))
            session['products'] = [p for p in session['products'] if p['id'] != pid]
            flash('Product deleted successfully!', 'success')
        elif action == 'upload_excel':
            f = request.files.get('excel_file')
            if f:
                import openpyxl
                from io import BytesIO
                wb = openpyxl.load_workbook(BytesIO(f.read()))
                ws = wb.active
                imported = 0
                prods = session['products']
                for row in ws.iter_rows(min_row=2, values_only=True):
                    if not row[0]:
                        continue
                    prods.append({
                        'id': len(prods) + 1,
                        'name': str(row[0]),
                        'description': '',
                        'category': '',
                        'price': row[1] if len(row) > 1 else 0,
                        'date_issued': str(row[2]) if len(row) > 2 else date.today().isoformat(),
                        'created_at': datetime.now().strftime('%Y-%m-%d %H:%M:%S'),
                    })
                    imported += 1
                session['products'] = prods
                flash(f'Successfully imported {imported} products from Excel file!', 'success')
        return redirect(url_for('products'))

    return render_template('products.html', products=session['products'])


# ---------- Cost (session-based) ----------

@app.route('/cost.php', methods=['GET', 'POST'])
@app.route('/cost', methods=['GET', 'POST'])
@login_required
def cost():
    if 'costs' not in session:
        session['costs'] = []

    if request.method == 'POST':
        action = request.form.get('action', '')
        if action == 'add_cost':
            costs = session['costs']
            costs.append({
                'id': len(costs) + 1,
                'product_name': request.form.get('product_name', ''),
                'unit_cost': request.form.get('unit_cost', ''),
                'updated_at': datetime.now().strftime('%Y-%m-%d %H:%M:%S'),
            })
            session['costs'] = costs
            flash('Cost added successfully!', 'success')
        elif action == 'delete_cost':
            cid = int(request.form.get('id', 0))
            session['costs'] = [c for c in session['costs'] if c['id'] != cid]
            flash('Cost deleted successfully!', 'success')
        return redirect(url_for('cost'))

    return render_template('cost.html', costs=session['costs'])


# ---------- Quantity List (session-based) ----------

@app.route('/quantity-list.php', methods=['GET', 'POST'])
@app.route('/quantity-list', methods=['GET', 'POST'])
@login_required
def quantity_list():
    if 'quantities' not in session:
        session['quantities'] = []

    if request.method == 'POST':
        action = request.form.get('action', '')
        if action == 'add_quantity':
            qs = session['quantities']
            qs.append({
                'id': len(qs) + 1,
                'product_name': request.form.get('product_name', ''),
                'quantity': request.form.get('quantity', ''),
                'unit': request.form.get('unit', ''),
                'updated_at': datetime.now().strftime('%Y-%m-%d %H:%M:%S'),
            })
            session['quantities'] = qs
            flash('Quantity added successfully!', 'success')
        elif action == 'delete_quantity':
            qid = int(request.form.get('id', 0))
            session['quantities'] = [q for q in session['quantities'] if q['id'] != qid]
            flash('Quantity deleted successfully!', 'success')
        return redirect(url_for('quantity_list'))

    return render_template('quantity_list.html', quantities=session['quantities'])


# ---------- Quantity Issued (session-based) ----------

@app.route('/quantity-issued.php', methods=['GET', 'POST'])
@app.route('/quantity-issued', methods=['GET', 'POST'])
@login_required
def quantity_issued():
    if 'issued' not in session:
        session['issued'] = []

    if request.method == 'POST':
        action = request.form.get('action', '')
        if action == 'add_issued':
            iss = session['issued']
            iss.append({
                'id': len(iss) + 1,
                'product_name': request.form.get('product_name', ''),
                'quantity': request.form.get('quantity', ''),
                'issued_to': request.form.get('issued_to', ''),
                'date_issued': datetime.now().strftime('%Y-%m-%d %H:%M:%S'),
            })
            session['issued'] = iss
            flash('Quantity issued successfully!', 'success')
        elif action == 'delete_issued':
            iid = int(request.form.get('id', 0))
            session['issued'] = [i for i in session['issued'] if i['id'] != iid]
            flash('Record deleted successfully!', 'success')
        return redirect(url_for('quantity_issued'))

    return render_template('quantity_issued.html', issued=session['issued'])


# ─── Run ─────────────────────────────────────────────────────────────────────

if __name__ == '__main__':
    app.run(debug=True, port=5000)
