<?php
include 'connect.php';
$conn = getDBConnection();

function executeQuery($conn, $sql)
{
    $stmt = oci_parse($conn, $sql);
    if (!oci_execute($stmt)) {
        $e = oci_error($stmt);
        echo "<p style='color:red;'>Error: {$e['message']}</p>";
    }
    oci_free_statement($stmt);
}

function safeDrop($conn, $sql)
{
    $stmt = oci_parse($conn, $sql);
    @oci_execute($stmt); // Suppress errors if they don't exist
    oci_free_statement($stmt);
}

safeDrop($conn, "DROP TRIGGER trg_users_pk");
safeDrop($conn, "DROP SEQUENCE user_seq");
safeDrop($conn, "DROP TABLE users CASCADE CONSTRAINTS");

safeDrop($conn, "DROP TRIGGER trg_shops_pk");
safeDrop($conn, "DROP SEQUENCE shop_seq");
safeDrop($conn, "DROP TABLE shops CASCADE CONSTRAINTS");

safeDrop($conn, "DROP TRIGGER trg_product_pk");
safeDrop($conn, "DROP SEQUENCE product_seq");
safeDrop($conn, "DROP TABLE product CASCADE CONSTRAINTS");

safeDrop($conn, "DROP TRIGGER trg_cart_pk");
safeDrop($conn, "DROP SEQUENCE cart_seq");
safeDrop($conn, "DROP TABLE cart CASCADE CONSTRAINTS");

safeDrop($conn, "DROP TRIGGER trg_review_pk");
safeDrop($conn, "DROP SEQUENCE review_seq");
safeDrop($conn, "DROP TABLE reviews CASCADE CONSTRAINTS");

safeDrop($conn, "DROP TRIGGER trg_orders_pk");
safeDrop($conn, "DROP SEQUENCE orders_seq");
safeDrop($conn, "DROP TABLE orders CASCADE CONSTRAINTS");

safeDrop($conn, "DROP TABLE product_cart CASCADE CONSTRAINTS");

safeDrop($conn, "DROP TRIGGER trg_collection_slot_pk");
safeDrop($conn, "DROP SEQUENCE collection_slot_seq");
safeDrop($conn, "DROP TABLE collection_slot CASCADE CONSTRAINTS");

safeDrop($conn, "DROP TRIGGER trg_payment_pk");
safeDrop($conn, "DROP SEQUENCE payment_seq");
safeDrop($conn, "DROP TABLE payment CASCADE CONSTRAINTS");

safeDrop($conn, "DROP TRIGGER trg_discount_pk");
safeDrop($conn, "DROP SEQUENCE discount_seq");
safeDrop($conn, "DROP TABLE discount CASCADE CONSTRAINTS");

safeDrop($conn, "DROP TRIGGER trg_report_pk");
safeDrop($conn, "DROP SEQUENCE report_seq");
safeDrop($conn, "DROP TABLE report CASCADE CONSTRAINTS");

safeDrop($conn, "DROP TABLE product_report CASCADE CONSTRAINTS");

safeDrop($conn, "DROP TRIGGER trg_wishlist_pk");
safeDrop($conn, "DROP SEQUENCE wishlist_seq");
safeDrop($conn, "DROP TABLE wishlist CASCADE CONSTRAINTS");

safeDrop($conn, "DROP TABLE wishlist_product CASCADE CONSTRAINTS");

safeDrop($conn, "DROP TRIGGER trg_coupon_pk");
safeDrop($conn, "DROP SEQUENCE coupon_seq");
safeDrop($conn, "DROP TABLE coupon CASCADE CONSTRAINTS");

// Reorder the table creation sequence
if ($conn) {

    // USERS TABLE
    executeQuery($conn, "
        CREATE TABLE users (
            user_id NUMBER PRIMARY KEY,
            full_name VARCHAR2(150) NOT NULL,
            email VARCHAR2(100) UNIQUE NOT NULL,
            contact_no VARCHAR2(20) NOT NULL,
            password VARCHAR2(255) NOT NULL,
            verification_code VARCHAR2(100),
            role VARCHAR2(10) DEFAULT 'customer' CHECK (role IN ('customer', 'trader', 'admin')),
            created_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            status VARCHAR2(10) DEFAULT 'pending' CHECK (status IN ('active', 'inactive', 'pending'))
        )
    ");
    executeQuery($conn, "CREATE SEQUENCE user_seq START WITH 1 INCREMENT BY 1");
    executeQuery($conn, "
        CREATE OR REPLACE TRIGGER trg_users_pk
        BEFORE INSERT ON users
        FOR EACH ROW
        BEGIN
            SELECT user_seq.NEXTVAL INTO :new.user_id FROM dual;
        END;");

    // CART TABLE
    executeQuery($conn, "
        CREATE TABLE cart (
            cart_id NUMBER PRIMARY KEY,
            user_id NUMBER NOT NULL,
            add_date DATE,
            CONSTRAINT fk_cart_user FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
        )
    ");
    executeQuery($conn, "CREATE SEQUENCE cart_seq START WITH 1 INCREMENT BY 1");
    executeQuery($conn, "
        CREATE OR REPLACE TRIGGER trg_cart_pk
        BEFORE INSERT ON cart
        FOR EACH ROW
        BEGIN
            SELECT cart_seq.NEXTVAL INTO :new.cart_id FROM dual;
        END;
    ");

    // SHOPS TABLE
    executeQuery($conn, "
        CREATE TABLE shops (
            shop_id NUMBER PRIMARY KEY,
            user_id NUMBER NOT NULL,
            shop_category VARCHAR2(20) CHECK (shop_category IN ('butcher', 'greengrocer', 'fishmonger', 'bakery', 'delicatessen')),
            shop_name VARCHAR2(255) NOT NULL,
            registration_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            description CLOB,
            shop_email VARCHAR2(100),
            contact_no VARCHAR2(20) NOT NULL,
            CONSTRAINT fk_shop_user FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
        )
    ");

    executeQuery($conn, "CREATE SEQUENCE shop_seq START WITH 1 INCREMENT BY 1");
    executeQuery($conn, "
        CREATE OR REPLACE TRIGGER trg_shops_pk
        BEFORE INSERT ON shops
        FOR EACH ROW
        BEGIN
            SELECT shop_seq.NEXTVAL INTO :new.shop_id FROM dual;
        END;
    ");

    // PRODUCTS TABLE
    executeQuery($conn, "
        CREATE TABLE product (
            product_id NUMBER PRIMARY KEY,
            product_name VARCHAR2(100),
            description CLOB,
            price NUMBER NOT NULL,
            stock NUMBER NOT NULL,
            min_order NUMBER NOT NULL,
            max_order NUMBER NOT NULL,
            product_image VARCHAR2(100),
            add_date DATE,
            product_status VARCHAR2(20),
            shop_id NUMBER NOT NULL,
            user_id NUMBER NOT NULL,
            product_category_name VARCHAR2(20) NOT NULL,
            CONSTRAINT fk_product_user FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
            CONSTRAINT fk_product_shop FOREIGN KEY (shop_id) REFERENCES shops(shop_id) ON DELETE CASCADE
        )
    ");

    executeQuery($conn, "CREATE SEQUENCE product_seq START WITH 1 INCREMENT BY 1");

    executeQuery($conn, "
        CREATE OR REPLACE TRIGGER trg_product_pk
        BEFORE INSERT ON product
        FOR EACH ROW
        BEGIN
            SELECT product_seq.NEXTVAL INTO :new.product_id FROM dual;
        END;
    ");

    // PRODUCT_CART TABLE
    executeQuery($conn, "
        CREATE TABLE product_cart (
            cart_id NUMBER,
            product_id NUMBER,
            quantity NUMBER NOT NULL,
            CONSTRAINT pk_product_cart PRIMARY KEY (cart_id, product_id),
            CONSTRAINT fk_pc_cart FOREIGN KEY (cart_id) REFERENCES cart(cart_id) ON DELETE CASCADE,
            CONSTRAINT fk_pc_product FOREIGN KEY (product_id) REFERENCES product(product_id) ON DELETE CASCADE
        )
    ");

    // REVIEW TABLE
    executeQuery($conn, "
        CREATE TABLE reviews (
            review_id NUMBER PRIMARY KEY,
            review_rating NUMBER,
            review CLOB,
            review_date DATE,
            user_id NUMBER NOT NULL,
            product_id NUMBER NOT NULL,
            CONSTRAINT fk_review_user FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
            CONSTRAINT fk_review_product FOREIGN KEY (product_id) REFERENCES product(product_id) ON DELETE CASCADE
        )
    ");

    executeQuery($conn, "CREATE SEQUENCE review_seq START WITH 1 INCREMENT BY 1");
    executeQuery($conn, "
        CREATE OR REPLACE TRIGGER trg_review_pk
        BEFORE INSERT ON reviews
        FOR EACH ROW
        BEGIN
            SELECT review_seq.NEXTVAL INTO :new.review_id FROM dual;
        END;
    ");

    // COLLECTION_SLOT TABLE
    executeQuery($conn, "
        CREATE TABLE collection_slot (
            collection_slot_id NUMBER PRIMARY KEY,
            slot_date DATE,
            slot_day VARCHAR2(10),
            slot_time TIMESTAMP NOT NULL,
            total_order NUMBER
        )
    ");

    executeQuery($conn, "CREATE SEQUENCE collection_slot_seq START WITH 1 INCREMENT BY 1");
    executeQuery($conn, "
        CREATE OR REPLACE TRIGGER trg_collection_slot_pk
        BEFORE INSERT ON collection_slot
        FOR EACH ROW
        BEGIN
            SELECT collection_slot_seq.NEXTVAL INTO :new.collection_slot_id FROM dual;
        END;
    ");

    // COUPON TABLE
    executeQuery($conn, "
        CREATE TABLE coupon (
            coupon_id NUMBER PRIMARY KEY,
            coupon_code VARCHAR2(20) NOT NULL,
            start_date DATE NOT NULL,
            end_date DATE NOT NULL,
            description VARCHAR2(200) NOT NULL,
            coupon_discount_percent NUMBER(5,2) NOT NULL
        )
    ");

    executeQuery($conn, "CREATE SEQUENCE coupon_seq START WITH 1 INCREMENT BY 1");
    executeQuery($conn, "
        CREATE OR REPLACE TRIGGER trg_coupon_pk
        BEFORE INSERT ON coupon
        FOR EACH ROW
        BEGIN
            SELECT coupon_seq.NEXTVAL INTO :new.coupon_id FROM dual;
        END;
    ");

    // ORDERS TABLE
    executeQuery($conn, "
        CREATE TABLE orders (
            order_id NUMBER PRIMARY KEY,
            order_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            order_amount NUMBER,
            total_amount NUMBER,
            coupon_id NUMBER,
            status VARCHAR2(50),
            collection_slot_id NUMBER,
            user_id NUMBER NOT NULL,
            cart_id NUMBER NOT NULL,
            CONSTRAINT fk_order_user FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
            CONSTRAINT fk_order_cart FOREIGN KEY (cart_id) REFERENCES cart(cart_id) ON DELETE CASCADE,
            CONSTRAINT fk_order_coupon FOREIGN KEY (coupon_id) REFERENCES coupon(coupon_id) ON DELETE CASCADE,
            CONSTRAINT fk_order_slot FOREIGN KEY (collection_slot_id) REFERENCES collection_slot(collection_slot_id) ON DELETE CASCADE
        )
    ");
    executeQuery($conn, "CREATE SEQUENCE orders_seq START WITH 1 INCREMENT BY 1");
    executeQuery($conn, "
        CREATE OR REPLACE TRIGGER trg_orders_pk
        BEFORE INSERT ON orders
        FOR EACH ROW
        BEGIN
            SELECT orders_seq.NEXTVAL INTO :new.order_id FROM dual;
        END;
    ");

    // PAYMENT TABLE
    executeQuery($conn, "
        CREATE TABLE payment (
            payment_id NUMBER PRIMARY KEY,
            payment_date DATE,
            amount NUMBER,
            payment_method VARCHAR2(50),
            payment_status VARCHAR2(20),
            order_id NUMBER NOT NULL,
            user_id NUMBER NOT NULL,
            CONSTRAINT fk_payment_user FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
            CONSTRAINT fk_payment_order FOREIGN KEY (order_id) REFERENCES orders(order_id) ON DELETE CASCADE
        )
    ");

    executeQuery($conn, "CREATE SEQUENCE payment_seq START WITH 1 INCREMENT BY 1");
    executeQuery($conn, "
        CREATE OR REPLACE TRIGGER trg_payment_pk
        BEFORE INSERT ON payment
        FOR EACH ROW
        BEGIN
            SELECT payment_seq.NEXTVAL INTO :new.payment_id FROM dual;
        END;
    ");

    // DISCOUNT TABLE
    executeQuery($conn, "
        CREATE TABLE discount (
            discount_id NUMBER PRIMARY KEY,
            discount_percentage NUMBER(5,2),
            product_id NUMBER NOT NULL,
            CONSTRAINT fk_discount_product FOREIGN KEY (product_id) REFERENCES product(product_id) ON DELETE CASCADE
        )
    ");

    executeQuery($conn, "CREATE SEQUENCE discount_seq START WITH 1 INCREMENT BY 1");
    executeQuery($conn, "
        CREATE OR REPLACE TRIGGER trg_discount_pk
        BEFORE INSERT ON discount
        FOR EACH ROW
        BEGIN
            SELECT discount_seq.NEXTVAL INTO :new.discount_id FROM dual;
        END;
    ");

    // REPORT TABLE
    executeQuery($conn, "
        CREATE TABLE report (
            report_id NUMBER PRIMARY KEY,
            report_type VARCHAR2(50),
            report_title VARCHAR2(100),
            report_date DATE,
            report_description VARCHAR2(4000) NOT NULL,
            order_id NUMBER,
            user_id NUMBER NOT NULL,
            CONSTRAINT fk_report_order FOREIGN KEY (order_id) REFERENCES orders(order_id) ON DELETE CASCADE,
            CONSTRAINT fk_report_user FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
        )
    ");

    executeQuery($conn, "CREATE SEQUENCE report_seq START WITH 1 INCREMENT BY 1");
    executeQuery($conn, "
        CREATE OR REPLACE TRIGGER trg_report_pk
        BEFORE INSERT ON report
        FOR EACH ROW
        BEGIN
            SELECT report_seq.NEXTVAL INTO :new.report_id FROM dual;
        END;
    ");

    // PRODUCT_REPORT TABLE
    executeQuery($conn, "
        CREATE TABLE product_report (
            product_id NUMBER NOT NULL,
            report_id NUMBER NOT NULL,
            CONSTRAINT pk_product_report PRIMARY KEY (product_id, report_id),
            CONSTRAINT fk_pr_product FOREIGN KEY (product_id) REFERENCES product(product_id) ON DELETE CASCADE,
            CONSTRAINT fk_pr_report FOREIGN KEY (report_id) REFERENCES report(report_id) ON DELETE CASCADE
        )
    ");

    // WISHLIST TABLE
    executeQuery($conn, "
        CREATE TABLE wishlist (
            wishlist_id NUMBER PRIMARY KEY,
            no_of_items NUMBER NOT NULL,
            user_id NUMBER NOT NULL,
            CONSTRAINT fk_wishlist_user FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
        )
    ");

    executeQuery($conn, "CREATE SEQUENCE wishlist_seq START WITH 1 INCREMENT BY 1");
    executeQuery($conn, "
        CREATE OR REPLACE TRIGGER trg_wishlist_pk
        BEFORE INSERT ON wishlist
        FOR EACH ROW
        BEGIN
            SELECT wishlist_seq.NEXTVAL INTO :new.wishlist_id FROM dual;
        END;
    ");

    // WISHLIST_PRODUCT TABLE
    executeQuery($conn, "
        CREATE TABLE wishlist_product (
            wishlist_id NUMBER NOT NULL,
            product_id NUMBER NOT NULL,
            added_date DATE NOT NULL,
            CONSTRAINT pk_wishlist_product PRIMARY KEY (wishlist_id, product_id),
            CONSTRAINT fk_wp_wishlist FOREIGN KEY (wishlist_id) REFERENCES wishlist(wishlist_id) ON DELETE CASCADE,
            CONSTRAINT fk_wp_product FOREIGN KEY (product_id) REFERENCES product(product_id) ON DELETE CASCADE
        )
    ");

    // INSERT USERS
    $users = [
        ['full_name' => 'Admin User', 'email' => 'admin@example.com', 'contact_no' => '1234567890', 'password' => 'admin123', 'role' => 'admin'],
        ['full_name' => 'Trader User', 'email' => 'trader@example.com', 'contact_no' => '0987654321', 'password' => 'trader123', 'role' => 'trader'],
        ['full_name' => 'Customer User', 'email' => 'customer@example.com', 'contact_no' => '1122334455', 'password' => 'customer123', 'role' => 'customer']
    ];

    foreach ($users as $user) {
        $check_sql = "SELECT COUNT(*) AS count FROM users WHERE email = :email";
        $stmt = oci_parse($conn, $check_sql);
        oci_bind_by_name($stmt, ":email", $user['email']);
        if (oci_execute($stmt)) {
            $row = oci_fetch_assoc($stmt);
            if ($row['COUNT'] == 0) {
                $insert_sql = "INSERT INTO users (full_name, email, contact_no, password, role, status)
                               VALUES (:full_name, :email, :contact_no, :password, :role, 'active')";
                $insert_stmt = oci_parse($conn, $insert_sql);
                oci_bind_by_name($insert_stmt, ":full_name", $user['full_name']);
                oci_bind_by_name($insert_stmt, ":email", $user['email']);
                oci_bind_by_name($insert_stmt, ":contact_no", $user['contact_no']);
                $hashed_password = password_hash($user['password'], PASSWORD_BCRYPT);
                oci_bind_by_name($insert_stmt, ":password", $hashed_password);
                oci_bind_by_name($insert_stmt, ":role", $user['role']);

                if (oci_execute($insert_stmt)) {
                    echo "Inserted user: {$user['full_name']}<br>";
                } else {
                    echo "Failed to insert: {$user['full_name']}<br>";
                }
                oci_free_statement($insert_stmt);
            } else {
                echo "User {$user['email']} already exists. Skipping...<br>";
            }
        } else {
            echo "Failed to check if {$user['email']} exists.<br>";
        }
        oci_free_statement($stmt);
    }

    oci_close($conn);
} else {
    echo "Could not connect to database.<br>";
}