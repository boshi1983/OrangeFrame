create database if not exists demo;
create table if not exists my_table
(
    id         int         null,
    first_name varchar(16) null,
    last_name  varchar(16) null,
    gender      varchar(16) null
);

INSERT INTO demo.my_table (id, first_name, last_name, gender) VALUES (10001, 'Georgi', 'Facello', 'M');
INSERT INTO demo.my_table (id, first_name, last_name, gender) VALUES (10002, 'Bezalel', 'Simmel', 'F');
INSERT INTO demo.my_table (id, first_name, last_name, gender) VALUES (10003, 'Parto', 'Bamford', 'M');
INSERT INTO demo.my_table (id, first_name, last_name, gender) VALUES (10004, 'Chirstian', 'Koblick', 'M');
INSERT INTO demo.my_table (id, first_name, last_name, gender) VALUES (10005, 'Kyoichi', 'Maliniak', 'M');
INSERT INTO demo.my_table (id, first_name, last_name, gender) VALUES (10006, 'Anneke', 'Preusig', 'F');
INSERT INTO demo.my_table (id, first_name, last_name, gender) VALUES (10007, 'Tzvetan', 'Zielinski', 'F');
INSERT INTO demo.my_table (id, first_name, last_name, gender) VALUES (10008, 'Saniya', 'Kalloufi', 'M');
INSERT INTO demo.my_table (id, first_name, last_name, gender) VALUES (10009, 'Sumant', 'Peac', 'F');
INSERT INTO demo.my_table (id, first_name, last_name, gender) VALUES (10010, 'Duangkaew', 'Piveteau', 'F');