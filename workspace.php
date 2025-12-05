<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Add Company | Email Management System</title>
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
      font-family: "Poppins", sans-serif;
    }

    body {
      background-color: #f5f7fb;
      display: flex;
      justify-content: center;
      align-items: center;
      height: 100vh;
    }

    .container {
      background: #fff;
      padding: 30px 40px;
      border-radius: 12px;
      box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
      width: 100%;
      max-width: 400px;
    }

    h2 {
      text-align: center;
      color: #333;
      margin-bottom: 20px;
    }

    .form-group {
      margin-bottom: 15px;
    }

    label {
      display: block;
      margin-bottom: 6px;
      color: #444;
      font-weight: 500;
    }

    input {
      width: 100%;
      padding: 10px;
      border: 1px solid #ccc;
      border-radius: 6px;
      outline: none;
      transition: 0.3s;
    }

    input:focus {
      border-color: #4a90e2;
      box-shadow: 0 0 4px rgba(74, 144, 226, 0.3);
    }

    button {
      width: 100%;
      padding: 12px;
      background: #4a90e2;
      border: none;
      border-radius: 6px;
      color: #fff;
      font-size: 16px;
      cursor: pointer;
      transition: 0.3s;
    }

    button:hover {
      background: #3b7cc0;
    }

    .note {
      text-align: center;
      font-size: 14px;
      color: #777;
      margin-top: 15px;
    }
  </style>
</head>
<body>
  <div class="container">
    <h2>Add Your Company</h2>
    <form action="#" method="POST">
      <div class="form-group">
        <label for="companyName">Company Name</label>
        <input type="text" id="companyName" name="companyName" placeholder="Enter company name" required />
      </div>

      <div class="form-group">
        <label for="companyEmail">Company Email</label>
        <input type="email" id="companyEmail" name="companyEmail" placeholder="Enter company email" required />
      </div>

      <div class="form-group">
        <label for="companyDomain">Company Domain</label>
        <input type="text" id="companyDomain" name="companyDomain" placeholder="e.g. example.com" />
      </div>

      <button type="submit">Add Company</button>

      <p class="note">You can add more companies later from your dashboard.</p>
    </form>
  </div>
</body>
</html>
