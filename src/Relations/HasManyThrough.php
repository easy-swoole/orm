<?php
/**
 * 远程一对多  城市、用户、话题，三个表，话题表和城市表没有数据关联，需要经过中间带用户表来完成
 * city_id city_name
 * u_id u_name city_id
 * topic_id topic_content u_id
 * User: Siam
 * Date: 2020/2/27
 * Time: 11:21
 */

namespace EasySwoole\ORM\Relations;


class HasManyThrough
{

}