<?php
namespace hng2_modules\comments;

use hng2_base\config;
use hng2_base\repository\abstract_repository;

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
    
        return $database->exec("
            insert into {$this->table_name} (
                id_post             ,
                id_comment          ,
                parent_comment      ,
                
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
     * @param $id_post
     *
     * @return object {where:array, limit:int, offset:int, order:string}
     */
    public function build_find_params_for_post_in_index($id_post)
    {
        global $settings;
        
        $where = array(
            "id_post = '$id_post'",
            "status = 'published'"
        );
        
        $limit  = $settings->get("modules:comments.items_per_page");
        $offset = (int) $_GET["offset"];
        $order  = "creation_date desc";
        
        if( empty($limit) ) $limit = 30;
        
        return (object) array(
            "where"  => $where,
            "limit"  => $limit,
            "offset" => $offset,
            "order"  => $order
        );
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
            "order"  => $order
        );
    }
}
