CREATE TABLE herd_history (
  id INT PRIMARY KEY ASC,
  hash TEXT,
  summaryhash TEXT,
  prevhash TEXT,
  contents TEXT,
  publickey TEXT,
  signature TEXT,
  accepted INT,
  created TEXT
);
CREATE TABLE herd_vendors (
  id INT PRIMARY KEY ASC,
  name TEXT,
  created TEXT,
  modified TEXT
);
CREATE TABLE herd_vendor_keys (
  id INT PRIMARY KEY ASC,
  trusted INT,
  vendor INT REFERENCES herd_vendors(id),
  publickey TEXT,
  history_create INT REFERENCES herd_history(id),
  history_revoke INT NULL REFERENCES herd_history(id),
  name TEXT,
  created TEXT,
  modified TEXT
);
CREATE TABLE herd_products (
  id INT PRIMARY KEY ASC,
  vendor INT REFERENCES herd_vendors(id),
  name TEXT,
  created TEXT,
  modified TEXT
);
CREATE TABLE herd_product_updates (
  id INT PRIMARY KEY ASC,
  product INT REFERENCES herd_products(id),
  history INT REFERENCES herd_history(id),
  version TEXT,
  body TEXT,
  publickey INT REFERENCES herd_vendor_keys(id),
  signature TEXT,
  created TEXT,
  modified TEXT
);
