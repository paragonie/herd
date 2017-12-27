CREATE TABLE herd_history (
  id INTEGER PRIMARY KEY,
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
  id INTEGER PRIMARY KEY,
  name TEXT,
  created TEXT,
  modified TEXT
);
CREATE TABLE herd_vendor_keys (
  id INTEGER PRIMARY KEY,
  trusted INT,
  vendor INTEGER REFERENCES herd_vendors(id),
  publickey TEXT,
  history_create INTEGER REFERENCES herd_history(id),
  history_revoke INTEGER NULL REFERENCES herd_history(id),
  name TEXT,
  created TEXT,
  modified TEXT
);
CREATE TABLE herd_products (
  id INTEGER PRIMARY KEY,
  vendor INTEGER REFERENCES herd_vendors(id),
  name TEXT,
  created TEXT,
  modified TEXT
);
CREATE TABLE herd_product_updates (
  id INTEGER PRIMARY KEY,
  product INTEGER REFERENCES herd_products(id),
  history INTEGER REFERENCES herd_history(id),
  version TEXT,
  body TEXT,
  publickey INTEGER REFERENCES herd_vendor_keys(id),
  signature TEXT,
  created TEXT,
  modified TEXT
);
