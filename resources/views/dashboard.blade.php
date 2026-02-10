<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Mabini Inventory System</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Poppins', sans-serif;
            background: #f5f5f5;
        }
        .navbar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 15px 30px;
            color: white;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .navbar h1 {
            font-size: 20px;
        }
        .navbar form {
            display: inline;
        }
        .navbar button {
            background: rgba(255,255,255,0.2);
            border: 1px solid white;
            color: white;
            padding: 8px 20px;
            border-radius: 5px;
            cursor: pointer;
            transition: all 0.3s;
        }
        .navbar button:hover {
            background: rgba(255,255,255,0.3);
        }
        .container {
            padding: 30px;
            max-width: 1200px;
            margin: 0 auto;
        }
        .welcome-box {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <h1>Mabini Inventory System - Dashboard</h1>
        <div>
            <span>Welcome, {{ Auth::user()->name ?? Auth::user()->email }}</span>
            <form method="POST" action="{{ route('logout') }}" style="display: inline;">
                @csrf
                <button type="submit">Logout</button>
            </form>
        </div>
    </nav>

    <div class="container">
        <div class="welcome-box">
            <h2>Welcome to Mabini Inventory System</h2>
            <p>You are successfully logged in!</p>
            <p style="margin-top: 10px; color: #666;">Start managing your inventory here.</p>
        </div>
    </div>
</body>
</html>
