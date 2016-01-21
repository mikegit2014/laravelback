<div class="common-top-nav">
    <div style="float:right;margin:0;">
    	<?php if(empty($userInfo)): ?>
			<a href="<?php echo route('blog.login'); ?>" ><span>登录</span></a>
			<a href="<?php echo route('blog.reg'); ?>" ><span>注册</span></a>
		<?php else: ?>
			<a href="javascript:;" ><span><?php echo isset($userInfo['realname']) ? $userInfo['realname'] : '欢迎回来'; ?></span></a>
			<a href="<?php echo route('blog.login.out'); ?>" ><span>退出</span></a>
		<?php endif; ?>
    </div>
</div>
