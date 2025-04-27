
Built by https://www.blackbox.ai

---

```markdown
# Coffee Shop Sales Management System

## Project Overview

The Coffee Shop Sales Management System is a web application designed to streamline the sales process in a coffee shop environment. This system allows cashiers and admins to manage products, process sales, and view sales orders efficiently. It provides a simple interface for selling products, displaying categories and products, and handling payment transactions, ensuring that cashiers can serve customers effectively.

## Installation

To set up the Coffee Shop Sales Management System on your local machine, follow these steps:

1. **Clone the repository:**
   ```bash
   git clone <repository-url>
   cd coffee-shop-sales-management
   ```

2. **Set up the database:**
   - Create a new MySQL database.
   - Import the schema and data using the provided SQL scripts (if any).

3. **Configure the application:**
   - Copy the `config.sample.php` to `config.php` and update database connection details:
   ```php
   <?php
   $db = new PDO('mysql:host=your_host;dbname=your_db_name;charset=utf8mb4', 'your_username', 'your_password');
   ?>
   ```
   
4. **Set up web server:**
   - Ensure your web server (like Apache or Nginx) is configured to serve your project directory.

5. **Access the application:**
   - Open your web browser and go to `http://localhost/coffee-shop-sales-management/sell_product.php`.

## Usage

1. **User Roles:**
   - Log in as either an 'admin' or 'cashier'.
   - Only logged-in users with the appropriate role can access the functionality.

2. **Selling Products:**
   - Use the **Sell Product** page to add products to the order.
   - Process payments at the end of the order.

3. **Viewing Orders:**
   - Admins can view all orders, while cashiers can only view their own orders.
   - Click on the "View", "Print", or "Delete" buttons for managing each order.

## Features

- User authentication with session management.
- Product categorization with dynamic category filtering.
- Real-time order management with ability to add/remove items.
- Payment processing with automatic change calculation.
- Sales and order history view for both cashiers and admins.

## Dependencies

This project requires the following PHP extensions:
- PDO (for database interaction)
- JSON (for data interchange)

### Frontend Dependencies
CSS styling is done using Tailwind CSS and icons from Font Awesome:
- [Tailwind CSS](https://tailwindcss.com/)
- [Font Awesome](https://fontawesome.com/)

## Project Structure

The project structure is organized as follows:

```
/coffee-shop-sales-management
│
├── sell_product.php         # Main page for selling products.
├── sales_view.php           # Page for viewing sales orders.
├── order_details.php        # API for retrieving specific order details.
├── update_order.php         # API for updating orders.
├── delete_order.php         # API for deleting orders.
├── diagnostic_check_sales_orders.php  # Utility script for checking sales orders.
├── config.php               # Database connection configuration.
│
└── <other supportive files>  # Additional scripts, utilities, and assets.
```

## Conclusion

This Coffee Shop Sales Management System aims to digitize and simplify the sales processes of a coffee shop, making it easier for staff to manage products and sales transactions efficiently. Contributions to improve the functionality and user experience are welcome.
```