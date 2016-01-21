<?php namespace App\Services\Admin\Group\Validate;

use Validator, Lang;
use App\Services\BaseValidate;

/**
 * 用户组列表表单验证
 *
 * @author jiang <mylampblog@163.com>
 */
class Group extends BaseValidate
{
    /**
     * 增加用户组的时候的表单验证
     *
     * @access public
     */
    public function add(\App\Services\Admin\Group\Param\GroupSave $data)
    {
        $rules = array(
            'group_name' => 'required',
            'level' => 'required|numeric',
        );
        
        $messages = array(
            'group_name.required' => Lang::get('group.group_name_empty'),
            'level.required' => Lang::get('group.group_level_empty'),
            'level.numeric' => Lang::get('group.group_level_empty'),
        );
        
        $validator = Validator::make($data->toArray(), $rules, $messages);
        if($validator->fails())
        {
            $this->errorMsg = $validator->messages()->first();
            return false;
        }
        return true;
    }
    
    /**
     * 编辑用户组的时候的表单验证
     *
     * @access public
     */
    public function edit(\App\Services\Admin\Group\Param\GroupSave $data)
    {
        return $this->add($data);
    }

    /**
     * 验证ID
     *
     * @param array $ids
     * @return array IDS
     */
    public function deleteIds(array $ids)
    {
        foreach($ids as $key => $value) {
            if( ! ($ids[$key] = url_param_decode($value)) ) {
                return false;
            }
        }

        return array_map('intval', $ids);
    }
    
}
