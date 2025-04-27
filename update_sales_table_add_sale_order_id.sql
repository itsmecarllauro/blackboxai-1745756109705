-- SQL script to add sale_order_id column to sales table and set up foreign key constraint

ALTER TABLE sales
ADD COLUMN sale_order_id INT NULL AFTER id;

ALTER TABLE sales
ADD CONSTRAINT fk_sale_order
FOREIGN KEY (sale_order_id) REFERENCES sales_orders(id) ON DELETE CASCADE;
