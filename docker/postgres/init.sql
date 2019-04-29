create table configuration
(
    id serial constraint configuration_pk primary key,
    option varchar(255),
    value varchar(255)
);
create table elevators
(
    id serial constraint elevators_pk primary key,
    floor int
);
create table orders
(
    id serial constraint orders_pk primary key,
    floor int,
    status int,
    elevator_id int
);
create table statistics
(
    id serial constraint statistics_pk primary key,
    elevator_id int,
    order_id int,
    from_floor int,
    to_floor int,
    direction int
);
create index option__index on configuration (option);
create index statistics_elevator_id_index on statistics (elevator_id);
create index statistics_order_id_index on statistics (order_id);