<?php
namespace hng2_modules\comments;

use hng2_base\config;
use hng2_repository\abstract_repository;
use hng2_base\accounts_repository;
use hng2_tools\record_browser;

class comments_repository extends abstract_repository
{
    protected $row_class                = "\\hng2_modules\\comments\\comment_record";
    protected $table_name               = "comments";
    protected $key_column_name          = "id_comment";
    
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
        
        if( $account->level < config::MODERATOR_USER_LEVEL )
        {
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
        }
        
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
        
        $limit  = $settings->get("modules:comments.items_per_page");
        if( empty($limit) ) $limit = 30;
        
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
        
        return array($find_params, $comments_count, $final, $pagination);
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
    private function build_tree(array $elements, $parent_id = "", $path = "")
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
}
