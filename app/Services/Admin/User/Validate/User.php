<?php namespace App\Services\Admin\User\Validate;

use Validator, Lang;
use App\Services\BaseValidate;
use App\Services\Admin\Acl\Acl;

/**
 * 用户表单验证
 *
 * @author jiang <mylampblog@163.com>
 */
class User extends BaseValidate
{
    /**
     * 增加用户的时候的表单验证
     *
     * @access public
     */
    public function add(\App\Services\Admin\User\Param\UserSave $data)
    {
        $rules = array(
            'name'      => 'required',
            'realname'  => 'required',
            'password'  => 'required',
            'group_id'  => 'required|numeric|min:1',
            'mobile'    => 'required'
        );
        
        $messages = array(
            'name.required'      => Lang::get('user.account_name_empty'),
            'password.required'  => Lang::get('user.password_empty'),
            'realname.required'  => Lang::get('user.realname_empty'),
            'group_id.required'  => Lang::get('user.group_empty'),
            'group_id.numeric'   => Lang::get('user.group_empty'),
            'group_id.min'       => Lang::get('user.group_empty'),
            'mobile.required'    => Lang::get('user.mobile_empty')
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
     * 编辑用户的时候的表单验证
     *
     * @access public
     */
    public function edit(\App\Services\Admin\User\Param\UserSave $data)
    {
        $rules = array(
            'name'      => 'required',
            'realname'  => 'required',
            'group_id'  => 'required|numeric|min:1',
            'mobile'    => 'required'
        );
        
        $messages = array(
            'name.required'      => Lang::get('user.account_name_empty'),
            'realname.required'  => Lang::get('user.realname_empty'),
            'group_id.required'  => Lang::get('user.group_empty'),
            'group_id.numeric'   => Lang::get('user.group_empty'),
            'group_id.min'       => Lang::get('user.group_empty'),
            'mobile.required'    => Lang::get('user.mobile_empty')
        );
        
        if( ! empty($data->password))
        {
            $rules['password'] = 'required';
            $messages['password.required'] = Lang::get('user.password_empty');
        }
        
        $validator = Validator::make($data->toArray(), $rules, $messages);
        if($validator->fails())
        {
            $this->errorMsg = $validator->messages()->first();
            return false;
        }
        return true;
    }
    
    /**
     * 修该用户密码的时候的表单验证
     *
     * @access public
     */
    public function password(\App\Services\Admin\User\Param\UserModifyPassword $data)
    {
        $rules = array(
            'oldPassword'  => 'required',
            'newPassword' => 'required',
            'newPasswordRepeat' => 'required',
        );
        
        $messages = array(
            'oldPassword.required'  => Lang::get('user.password_empty'),
            'newPassword.required'  => Lang::get('user.new_password_empty'),
            'newPasswordRepeat.required' => Lang::get('user.newPasswordRepeat')
        );

        $validator = Validator::make($data->toArray(), $rules, $messages);
        if($validator->fails())
        {
            $this->errorMsg = $validator->messages()->first();
            return false;
        }

        if($data->newPassword != $data->newPasswordRepeat)
        {
            $this->errorMsg = Lang::get('user.password_comfirm');
            return false;
        }

        return true;
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
