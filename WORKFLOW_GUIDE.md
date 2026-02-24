# 📋 Inventory Management Workflow Guide

## 🎯 Complete System Overview

Your Mabini Inventory System now has a complete workflow for managing inventory from purchase to distribution!

---

## 🔄 The Complete Workflow

### 1. **Receive Items** (Adding Stock)
📥 When you purchase or receive new items

### 2. **Issue Items** (Distributing to Offices)
📤 When offices request items

### 3. **Process Transactions** (Approve & Complete)
⚙️ Review and complete orders

### 4. **Reorder Management** (Monitor Low Stock)
🔄 Track items that need reordering

---

## 📥 How to Receive Items

**When to use:** When you receive new stock from suppliers or purchases

**Steps:**
1. Go to **Receive Items** in the sidebar
2. Click **"+ Record Receipt"**
3. Fill in:
   - **Item** - Select the item received
   - **Quantity Received** - How many units  
   - **Unit Cost** - Price per unit (auto-fills from item master)
   - **Supplier** (optional) - Who supplied the items
   - **Remarks** - PO number, delivery notes, etc.
4. Click **"Record Receipt"**

**What happens:**
- ✅ Stock quantity increases automatically
- ✅ Unit cost updates if changed
- ✅ Transaction recorded with reference number
- ✅ Stock movement logged

---

## 📤 How to Issue Items to Offices

**When to use:** When an office/department requests items

**Steps:**
1. Go to **Issue Items** in the sidebar
2. Click **"+ Create Issue Request"**
3. Fill in:
   - **Office/Department** - Which office needs the items
   - **Item** - What item they're requesting
   - **Quantity** - How many units needed
   - **Remarks** - Purpose or additional notes
4. Click **"Create Issue Request"**

**What happens:**
- ✅ Request created with "Pending" status
- ✅ Stock NOT yet deducted (only after approval & completion)
- ✅ Reference number generated (e.g., ISS-20260224-0001)
- ⚠️ Warning shown if insufficient stock

**Stock Indicator:**
- 🟢 Green = Enough stock available
- 🟡 Yellow = Low stock warning
- 🔴 Red = Out of stock

---

## ⚙️ How to Process Transactions

**When to use:** To approve, complete, or cancel pending requests

**Transaction Statuses:**
1. **Pending** 🟡 - Just created, awaiting review
2. **Approved** 🔵 - Reviewed and approved, ready to fulfill
3. **Completed** 🟢 - Fulfilled, stock updated
4. **Cancelled** 🔴 - Cancelled/rejected

**Steps:**

### Approve a Pending Request:
1. Go to **Process Transactions**
2. Find the pending transaction
3. Click **"✓ Approve"**
4. Transaction moves to "Approved" status

### Complete an Approved Request:
1. Find the approved transaction
2. Verify stock availability
3. Click **"✓ Complete"**
4. **Stock is automatically deducted**
5. Transaction moves to "Completed" status

### Cancel a Transaction:
1. Find the pending or approved transaction
2. Click **"✗ Cancel"**
3. Confirm cancellation
4. Transaction moves to "Cancelled" status (no stock changes)

**Filtering:**
- Filter by **Status**: All / Pending / Approved / Completed / Cancelled
- Filter by **Type**: All / Issue / Receive

---

## 🔄 How to Use Reorder Management

**When to use:** To monitor and reorder low stock items

**Priority Levels:**
- 🔴 **Out of Stock** - Urgent! No stock available
- 🟠 **Critical** - Very low (below 50% of reorder level)
- 🟡 **Low Stock** - Below reorder level

**What You'll See:**
- Current stock vs. reorder level
- Visual stock indicator bar
- Shortage amount
- **Suggested order quantity** (2x reorder level)
- Estimated reorder cost
- Supplier information (if available)

**Steps:**
1. Go to **Reorder Management**
2. Review items by priority
3. Check suggested order quantity
4. Click **"Order X units"** to go to Receive Items
5. Record the purchase when items arrive

---

## 💡 Best Practices

### Daily Tasks:
1. ✅ Check **Process Transactions** for pending approvals
2. ✅ Complete approved transactions
3. ✅ Record any received items immediately

### Weekly Tasks:
1. ✅ Review **Reorder Management** for low stock
2. ✅ Create purchase orders for critical items
3. ✅ Update item costs if prices changed

### Monthly Tasks:
1. ✅ Review completed transactions
2. ✅ Check inventory levels
3. ✅ Generate reports

---

## 📊 Understanding the Stats

### Issue Items Page:
- **Pending Issues** - Awaiting approval
- **Completed Today** - Fulfilled today
- **Total Transactions** - All issue transactions

### Receive Items Page:
- **Received Today** - Items received today
- **This Month** - Receipts this month
- **Total Value Received** - Total cost of all received items

### Process Transactions:
- **Pending Approval** - Need review
- **Approved** - Ready to fulfill
- **Completed Today** - Finished today

### Reorder Management:
- **Out of Stock** - Critical! Order immediately
- **Critical Stock** - Urgent reordering needed
- **Low Stock Items** - Below reorder level

---

## 🎯 Complete Workflow Example

### Scenario: Treasury Office needs 50 pens

**Step 1: Create Issue Request**
- Office: Treasury Office
- Item: Ballpen (Black) 0.7 TIP
- Quantity: 50
- Status: **Pending**

**Step 2: Approve Request**
- Review request in Process Transactions
- Click "✓ Approve"
- Status changes to: **Approved**

**Step 3: Complete/Fulfill**
- Verify stock available (current stock: 119)
- Click "✓ Complete"
- Stock automatically reduced: 119 → 69
- Status changes to: **Completed**

**Step 4: Reorder if Needed**
- Item appears in Reorder Management (stock now below reorder level)
- Review suggested order quantity
- Place order with supplier
- Receive items and record receipt

---

## 🛠️ Troubleshooting

### "Insufficient stock" error when completing
**Solution:** Check current stock in Item Master List. Cancel transaction or receive more items first.

### Transaction stuck in "Pending"
**Solution:** Go to Process Transactions and approve it.

### Stock not updating
**Solution:** Make sure to click "Complete" not just "Approve". Only completed transactions update stock.

### Item not showing in Issue Items dropdown
**Solution:** Check Item Master List - item status must be "Active".

---

## 🔒 Important Notes

1. **Stock is only deducted when transaction is COMPLETED**, not when created or approved
2. **Receive Items immediately updates stock** (marked as Completed automatically)
3. **Always record receipts** when items arrive to maintain accurate inventory
4. **Approve before completing** - gives you time to verify and prepare items
5. **Check Reorder Management regularly** to avoid stockouts

---

## 🚀 Quick Links

- **Add New Items:** Item Master List → + Add New Item
- **Create Issue:** Issue Items → + Create Issue Request
- **Receive Stock:** Receive Items → + Record Receipt
- **Approve Orders:** Process Transactions → ✓ Approve
- **Check Low Stock:** Reorder Management

---

**Need Help?**
- Check the system stats on the Dashboard
- Filter transactions to find specific items or offices
- Use the search function in Item Master List
- Click on item rows for detailed information

---

**Last Updated:** February 24, 2026
