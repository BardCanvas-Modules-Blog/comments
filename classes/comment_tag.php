<?php
namespace hng2_modules\comments;

use hng2_repository\abstract_record;

class comment_tag extends abstract_record
{
    public $id_comment;
    public $tag;
    public $date_attached;
    public $order_attached;
    
    public function set_new_id()
    {
    }
}
