CREATE DATABASE IF NOT EXISTS cast_app;
USE cast_app;

CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  first_name VARCHAR(50),
  last_name VARCHAR(50),
  city VARCHAR(50),
  state VARCHAR(50),
  setting VARCHAR(100),
  email VARCHAR(100) UNIQUE,
  password VARCHAR(255)
);
