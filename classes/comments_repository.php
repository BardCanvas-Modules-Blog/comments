<?php
namespace hng2_modules\comments;

use hng2_base\account_record;
use hng2_base\config;
use hng2_repository\abstract_repository;
use hng2_base\accounts_repository;
use hng2_tools\record_browser;

class comments_repository extends abstract_repository
{
    protected $row_class                = "\\hng2_modules\\comments\\comment_record";
    protected $table_name               = "comments";
    protected $key_column_name          = "id_comment";
    protected $additional_select_fields = array(
        # Replies for comment
        "(select count(id_comment) from comments c2 where c2.parent_comment = comments.id_comment) as _replies_count",
        # Tags
        "( select group_concat(tag order by date_attached asc, order_attached asc separator ',')
           from comment_tags where comment_tags.id_comment = comments.id_comment
           ) as tags_list",
        # Attachments
        "( select group_concat(id_media order by date_attached asc, order_attached asc separator ',')
           from comment_media where comment_media.id_comment = comments.id_comment
           ) as media_list",
    );
    
    /**
     * @var accounts_repository|null
     */
    protected static $accounts_repository = null;
    
    public function __construct()
    {
        global $settings;
        
        $days = (int) $settings->get("modules:comments.disable_new_after");
        
        $now            = date("Y-m-d H:i:s");
        $date_add       = "date_add((select publishing_date from posts where posts.id_post = comments.id_post), interval $days day)";
        $allow_comments = "select allow_comments from posts where posts.id_post = comments.id_post";
        
        if( empty($days) )
            $this->additional_select_fields[] =
                "($allow_comments) as _can_be_replied";
        else
            $this->additional_select_fields[] =
                "
                if(
                    $date_add > '$now',
                    ($allow_comments),
                    0
                ) as _can_be_replied
                ";
        
        parent::__construct();
    }
    
    /**
     * @param $id
     *
     * @return comment_record|null
     */
    public function get($id)
    {
        return parent::get($id);
    }
    
    /**
     * @param array  $where
     * @param int    $limit
     * @param int    $offset
     * @param string $order
     *
     * @return comment_record[]
     */
    public function find($where, $limit, $offset, $order)
    {
        return parent::find($where, $limit, $offset, $order);
    }
    
    /**
     * @param comment_record $record
     *
     * @return int
     */
    public function save($record)
    {
        global $database;
    
        $this->validate_record($record);
        $obj = $record->get_for_database_insertion();
        
        $obj->last_update = date("Y-m-d H:i:s");
        
        $res = $database->exec("
            insert into {$this->table_name} (
                id_post             ,
                id_comment          ,
                parent_comment      ,
                parent_author       ,
                indent_level        ,
                
                id_author           ,
                author_display_name ,
                author_email        ,
                author_url          ,
                
                content             ,
                status              ,
                
                creation_date       ,
                creation_ip         ,
                creation_host       ,
                creation_location   ,
                
                last_update         
            ) values (
                '{$obj->id_post             }',
                '{$obj->id_comment          }',
                '{$obj->parent_comment      }',
                '{$obj->parent_author       }',
                '{$obj->indent_level        }',
                
                '{$obj->id_author           }',
                '{$obj->author_display_name }',
                '{$obj->author_email        }',
                '{$obj->author_url          }',
                
                '{$obj->content             }',
                '{$obj->status              }',
                
                '{$obj->creation_date       }',
                '{$obj->creation_ip         }',
                '{$obj->creation_host       }',
                '{$obj->creation_location   }',
                
                '{$obj->last_update         }'         
            ) on duplicate key update
                content     = '{$obj->content             }',
                status      = '{$obj->status              }',
                last_update = '{$obj->last_update         }'
        ");
        
        $this->last_query = $database->get_last_query();
        return $res;
    }
    
    /**
     * @param comment_record $record
     *
     * @throws \Exception
     */
    public function validate_record($record)
    {
        if( ! $record instanceof comment_record )
            throw new \Exception(
                "Invalid object class! Expected: {$this->row_class}, received: " . get_class($record)
            );
    }
    
    /**
     * @param $id_post
     *
     * @return object {where:array, limit:int, offset:int, order:string}
     */
    public function build_find_params_for_post($id_post)
    {
        return $this->build_find_params(array("id_post = '$id_post'"));
    }
    
    /**
     * @param array $where
     *
     * @return object {where:array, limit:int, offset:int, order:string}
     */
    protected function build_find_params($where = array())
    {
        global $account, $settings;
        
        # if( $account->level < config::MODERATOR_USER_LEVEL )
        # {
            if( ! $account->_exists )
                $where[] = "status = 'published'";
            else
                $where[] = "
                    (
                        (id_author =  '{$account->id_account}' and status in ('published', 'reviewing'))
                        or
                        (id_author <> '{$account->id_account}' and status = 'published')
                    )
                ";
        # }
        
        $limit       = $settings->get("modules:comments.items_per_page");
        $offset      = (int) $_GET["offset"];
        $order       = "creation_date desc";
        
        if( empty($limit) ) $limit = 30;
        
        return (object) array(
            "where"  => $where,
            "limit"  => $limit,
            "offset" => $offset,
            "order"  => $order,
        );
    }
    
    /**
     * @param array $post_ids
     * 
     * @return array two dimensions: id_post, id_comment
     */
    public function get_for_multiple_posts(array $post_ids)
    {
        global $settings, $database;
        
        if( empty($post_ids) ) return array();
        
        $return     = array();
        $queries    = array();
        $author_ids = array();
        
        $limit  = $settings->get("modules:comments.items_per_index_entry");
        if( empty($limit) ) $limit = 10;
        
        foreach($post_ids as $post_id)
            $queries[] = "(
                select * from {$this->table_name} where status = 'published' and id_post = '$post_id'
                order by creation_date desc limit $limit
            )";
        
        $query = implode("\nunion\n", $queries);
        $this->last_query = $query;
        $res   = $database->query($query);
        
        if( $database->num_rows($res) == 0 ) return array();
        
        # Integration
        while($row = $database->fetch_object($res) )
        {
            $instance = new comment_record($row);
            $return[$row->id_post][$row->id_comment] = $instance;
            
            if( ! empty($instance->id_author) )
                $author_ids[] = $instance->id_author;
        }
        
        # Author additions
        if( ! empty($author_ids) )
        {
            $author_ids = array_unique($author_ids);
            
            $authors_repository = new accounts_repository();
            $authors            = $authors_repository->get_multiple($author_ids);
            
            /** @var comment_record[][] $return */
            foreach($return as $post_id => $comments)
                foreach($comments as $comment_id => $comment)
                    if( isset($authors[$comment->id_author]) )
                        $return[$post_id][$comment_id]->set_author($authors[$comment->id_author]);
        }
        
        return $return;
    }
    
    /**
     * Returns a flattened array of comment records for rendering in a single post
     * 
     * @param $id_post
     *
     * @return array
     */
    public function get_for_single_post($id_post)
    {
        $browser        = new record_browser("");
        $find_params    = $this->build_find_params_for_post($id_post);
        $comments_count = $this->get_record_count($find_params->where);
        $comments       = $this->find($find_params->where, $find_params->limit, $find_params->offset, $find_params->order);
        $pagination     = $browser->build_pagination($comments_count, $find_params->limit, $find_params->offset);
        
        if( count($comments) ) $comments = array_reverse($comments);
        
        $tree  = $this->build_tree($comments);
        $final = $this->flatten_tree($tree);
        
        $this->preload_authors($final);
        
        return array($find_params, $comments_count, $final, $pagination);
    }
    
    /**
     * @param comment_record[] $comments
     */
    private function preload_authors(array &$comments)
    {
        global $modules, $config;
        
        $author_ids = array();
        foreach( $comments as $item )
            if( ! empty($item->id_author) )
                $author_ids[] = $item->id_author;
        
        if( count($author_ids) > 0 )
        {
            $author_ids         = array_unique($author_ids);
            $authors_repository = new accounts_repository();
            $authors            = $authors_repository->get_multiple($author_ids);
            
            foreach( $comments as $index => &$item )
                $item->set_author($authors[$item->id_author]);
        }
        
        $config->globals["author_ids"] = $author_ids;
        $modules["comments"]->load_extensions("comments_repository_class", "preload_authors");
    }
    
    /**
     * Standard way to build the posts collection
     *
     * @param array  $where
     * @param int    $limit
     * @param int    $offset
     * @param string $order
     *
     * @return comment_record[]
     */
    public function lookup($where, $limit = 0, $offset = 0, $order = "")
    {
        $params = $this->build_find_params();
        
        if( empty($where)  ) $where  = array();
        if( empty($limit)  ) $limit  = $params->limit;
        if( empty($offset) ) $offset = $params->offset;
        if( empty($order)  ) $order  = $params->order;
        
        $where = array_merge($where, $params->where);
        
        return parent::find($where, $limit, $offset, $order);
    }
    
    /**
     * @param comment_record[] $elements
     * @param string            $parent_id
     * @param                   $path
     *
     * @return array
     */
    private function build_tree(array $elements, $parent_id = "0", $path = "")
    {
        $branch = array();
        
        foreach( $elements as $element )
        {
            if( $element->parent_comment == $parent_id )
            {
                $children = $this->build_tree($elements, $element->id_comment, "{$path}/{$element->id_comment}");
                if( $children ) $element->children = $children;
                $branch["{$path}/{$element->id_comment}"] = $element;
            }
        }
        
        return $branch;
    }
    
    private function flatten_tree(array $elements)
    {
        $return = array();
        
        foreach($elements as $id_path => $element)
        {
            $clone = clone $element;
            unset( $clone->children );
            $return[$id_path] = $clone;
            
            if( $element->children )
            {
                $element_children = $this->flatten_tree( $element->children );
                $return = array_merge($return, $element_children);
            }
        }
        
        return $return;
    }
    
    /**
     * @param $id_comment
     *
     * @return comment_tag[]
     *
     * @throws \Exception
     */
    public function get_tags($id_comment)
    {
        global $database;
        
        $res = $database->query("select * from comment_tags where id_comment = '$id_comment'");
        $this->last_query = $database->get_last_query();
        
        if( $database->num_rows($res) == 0 ) return array();
        
        $rows = array();
        while($row = $database->fetch_object($res))
            $rows[$row->tag] = new comment_tag($row);
        
        return $rows;
    }
    
    public function set_tags(array $list, $id_comment)
    {
        global $database;
        
        $actual_tags = $this->get_tags($id_comment);
        
        if( empty($actual_tags) && empty($list) ) return;
        
        $date = date("Y-m-d H:i:s");
        $inserts = array();
        $index   = 1;
        foreach($list as $tag)
        {
            if( ! isset($actual_tags[$tag]) ) $inserts[] = "('$id_comment', '$tag', '$date', '$index')";
            unset($actual_tags[$tag]);
            $index++;
        }
        
        if( ! empty($inserts) )
        {
            $database->exec(
                "insert into comment_tags (id_comment, tag, date_attached, order_attached) values "
                . implode(", ", $inserts)
            );
            $this->last_query = $database->get_last_query();
        }
        
        if( ! empty($actual_tags) )
        {
            $deletes = array();
            foreach($actual_tags as $tag => $object) $deletes[] = "'$tag'";
            $database->exec(
                "delete from comment_tags where id_comment = '$id_comment' and tag in (" . implode(", ", $deletes) . ")"
            );
            $this->last_query = $database->get_last_query();
        }
    }
    
    /**
     * @param $id_comment
     *
     * @return comment_media_item[]
     *
     * @throws \Exception
     */
    public function get_media_items($id_comment)
    {
        global $database;
        
        $res = $database->query("select * from comment_media where id_comment = '$id_comment' order by date_attached, order_attached");
        $this->last_query = $database->get_last_query();
        
        if( $database->num_rows($res) == 0 ) return array();
        
        $rows = array();
        while($row = $database->fetch_object($res))
            $rows[$row->id_media] = new comment_media_item($row);
        
        return $rows;
    }
    
    /**
     * @param array  $list
     * @param string $id_comment
     *
     * @return array
     */
    public function set_media_items(array $list, $id_comment)
    {
        global $database;
        
        $actual_items = $this->get_media_items($id_comment);
        
        if( empty($actual_items) && empty($list) ) return array();
        
        $date    = date("Y-m-d H:i:s");
        $inserts = array();
        $index   = 1;
        foreach($list as $id)
        {
            if( ! isset($actual_items[$id]) ) $inserts[] = "('$id_comment', '$id', '$date', '$index')";
            unset($actual_items[$id]);
            $index++;
        }
        
        if( ! empty($inserts) )
        {
            $database->exec(
                "insert into comment_media (id_comment, id_media, date_attached, order_attached) values "
                . implode(", ", $inserts)
            );
            $this->last_query = $database->get_last_query();
        }
        
        $deletes = array();
        if( ! empty($actual_items) )
        {
            foreach($actual_items as $id => $object) $deletes[] = "'$id'";
            $database->exec(
                "delete from comment_media where id_comment = '$id_comment' and id_media in (" . implode(", ", $deletes) . ")"
            );
            $this->last_query = $database->get_last_query();
        }
        
        return $deletes;
    }
    
    public function get_grouped_tag_counts($since = "", $min_hits = 10)
    {
        global $database;
        
        $min_hits = empty($min_hits) ? 10 : $min_hits;
        $having   = $min_hits == 1   ? "" : "having `count` >= '$min_hits'";
        
        if( empty($since) )
            $query = "
                select tag, count(tag) as `count` from comment_tags
                group by tag
                $having
                order by `count` desc
            ";
        else
            $query = "
                select tag, count(tag) as `count` from comment_tags
                where date_attached >= '{$since}'
                group by tag
                $having
                order by `count` desc
            ";
        
        $res = $database->query($query);
        if( $database->num_rows($res) == 0 ) return array();
        
        $return = array();
        while( $row = $database->fetch_object($res) )
            $return[$row->tag] = $row->count;
        
        return $return;
    }
    
    /**
     * @param comment_record[] $records
     *
     * @return account_record[]
     */
    public function get_all_authors(array $records)
    {
        if( empty($records) ) return array();
        
        $author_ids = array();
        foreach($records as $record)
            if( ! empty($record->id_author))
                $author_ids[] = $record->id_author;
        $author_ids = array_unique($author_ids);
        
        if( is_null(self::$accounts_repository) ) self::$accounts_repository = new accounts_repository();
        
        return self::$accounts_repository->get_multiple($author_ids);
    }
    
    public function change_status($id_comment, $new_status)
    {
        global $database;
        
        $res = $database->exec("update comments set status = '$new_status' where id_comment = '$id_comment'");
        $this->last_query = $database->get_last_query();
        
        return $res;
    }
    
    public function hide_all_published_by_auhtor($id_author)
    {
        global $database;
        
        $query = "
            update {$this->table_name} set status = 'hidden'
            where status = 'published' and id_author = '$id_author'
        ";
        $this->last_query = $database->get_last_query();
        return $database->exec($query);
    }
    
    public function unhide_all_published_by_auhtor($id_author)
    {
        global $database;
        
        $query = "
            update {$this->table_name} set status = 'published'
            where status = 'hidden' and id_author = '$id_author'
        ";
        $this->last_query = $database->get_last_query();
        return $database->exec($query);
    }
    
    public function empty_trash()
    {
        global $database;
        
        $boundary = date("Y-m-d 00:00:00", strtotime("today - 7 days"));
        
        $database->exec("
          delete from comment_mentions where id_comment in (
            select id_comment from comments where status = 'trashed'
            and creation_date < '$boundary'
          )
        ");
        
        $database->exec("
          delete from comment_tags where id_comment in (
            select id_comment from comments where status = 'trashed'
            and creation_date < '$boundary'
          )
        ");
        
        $database->exec("
          delete from comment_media where id_comment in (
            select id_comment from comments where status = 'trashed'
            and creation_date < '$boundary'
          )
        ");
        
        $database->exec("
          delete from comments where status = 'trashed'
          and creation_date < '$boundary'
        ");
    }
    
    /**
     * @param array $ids
     *
     * @return comment_record[]
     */
    public function get_multiple(array $ids)
    {
        if( count($ids) == 0 ) return array();
        
        $prepared_ids = array();
        foreach($ids as $id) $prepared_ids[] = "'$id'";
        $prepared_ids = implode(", ", $prepared_ids);
        
        $res = $this->find(array("id_comment in ($prepared_ids)"), 0, 0, "");
        if( count($res) == 0 ) return array();
        
        $return = array();
        foreach($res as $comment) $return[$comment->id_comment] = $comment;
        
        return $return;
    }
}
