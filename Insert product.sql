-- Insert a new product into the products table
INSERT INTO products (
    user_id,
    name,
    category_id,
    price,
    `condition`,
    description,
    days_ago,
    status,
    created_at
) VALUES (
    1,                       -- replace with actual user_id
    'Blue Jacket',           -- item name
    5,                       -- category_id (e.g. Jackets)
    19999,                   -- price in cents (199.99 ZAR)
    'New',                   -- condition
    'Warm winter jacket',    -- description
    0,                       -- days_ago (always 0 on insert)
    'available',             -- status
    NOW()                    -- created_at timestamp
);
