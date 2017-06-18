#文章点赞数量表
CREATE TABLE post_set(
post_id int not null comment '文章ID',
zan_count int not null comment '总赞数量',
update_at int(10) not null comment '更新时间'
)ENGINE=InnoDB DEFAULT CHARSET=utf8;

#用户文章点赞表
CREATE TABLE post_user(
post_id int not null comment '文章ID',
user_id int not null comment '用户iD',
create_at int(10) not null comment '点赞时间',
update_at int(10) not null comment '点赞更新时间',
status tinyint(1) not null comment '点赞状态',
list_update_at int(10) not null comment '点赞更新时间'
)ENGINE=InnoDB DEFAULT CHARSET=utf8;