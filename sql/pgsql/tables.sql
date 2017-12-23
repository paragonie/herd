CREATE TABLE herd_history (
  id BIGSERIAL PRIMARY KEY,
  hash TEXT,
  summaryhash TEXT,
  prevhash TEXT,
  contents TEXT,
  publickey TEXT,
  signature TEXT,
  accepted BOOLEAN DEFAULT FALSE,
  created TIMESTAMP
);
CREATE TABLE herd_vendors (
  id BIGSERIAL PRIMARY KEY,
  name TEXT,
  created TIMESTAMP,
  modified TIMESTAMP
);
CREATE TABLE herd_vendor_keys (
  id BIGSERIAL PRIMARY KEY,
  trusted BOOLEAN DEFAULT FALSE,
  vendor BIGINT REFERENCES herd_vendors(id),
  publickey TEXT,
  history_create BIGINT REFERENCES herd_history(id),
  history_revoke BIGINT NULL REFERENCES herd_history(id),
  name TEXT,
  created TIMESTAMP,
  modified TIMESTAMP
);
CREATE TABLE herd_products (
  id BIGSERIAL PRIMARY KEY,
  vendor BIGINT REFERENCES herd_vendors(id),
  name TEXT,
  created TIMESTAMP,
  modified TIMESTAMP
);
CREATE TABLE herd_product_updates (
  id BIGSERIAL PRIMARY KEY,
  product BIGINT REFERENCES herd_products(id),
  history BIGINT REFERENCES herd_history(id),
  version TEXT,
  body TEXT,
  publickey BIGINT REFERENCES herd_vendor_keys(id),
  signature TEXT,
  created TIMESTAMP,
  modified TIMESTAMP
);
