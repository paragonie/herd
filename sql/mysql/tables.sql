CREATE TABLE herd_history (
  id BIGINT PRIMARY KEY AUTO_INCREMENT,
  hash TEXT,
  summaryhash TEXT,
  prevhash TEXT,
  contents TEXT,
  publickey TEXT,
  signature TEXT,
  accepted BOOLEAN DEFAULT FALSE,
  created DATETIME
);
CREATE TABLE herd_vendors (
  id BIGINT PRIMARY KEY AUTO_INCREMENT,
  name TEXT,
  created DATETIME,
  modified DATETIME
);
CREATE TABLE herd_vendor_keys (
  id BIGINT PRIMARY KEY AUTO_INCREMENT,
  trusted BOOLEAN DEFAULT FALSE,
  vendor BIGINT REFERENCES herd_vendors(id),
  publickey TEXT,
  history_create BIGINT REFERENCES herd_history(id),
  history_revoke BIGINT NULL REFERENCES herd_history(id),
  name TEXT,
  created DATETIME,
  modified DATETIME
);
CREATE TABLE herd_products (
  id BIGINT PRIMARY KEY AUTO_INCREMENT,
  vendor BIGINT REFERENCES herd_vendors(id),
  name TEXT,
  created DATETIME,
  modified DATETIME
);
CREATE TABLE herd_product_updates (
  id BIGINT PRIMARY KEY AUTO_INCREMENT,
  product BIGINT REFERENCES herd_products(id),
  history BIGINT REFERENCES herd_history(id),
  version TEXT,
  body TEXT,
  publickey BIGINT REFERENCES herd_vendor_keys(id),
  signature TEXT,
  created DATETIME,
  modified DATETIME
);