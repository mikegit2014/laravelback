<div class="widget">
<h3 style="margin-top: 0px;">最新评论</h3>
<div class="widget-body">
    <ul class="icons list-unstyled">
        <?php if(isset($list) and is_array($list)): ?>
        <?php foreach($list as $key => $value): ?>
            <li><?php echo $key + 1; ?>、<a href="<?php echo route('blog.index.detail', ['id' => $value['id']]); ?>"><i class="icon-angle-right"></i> <?php echo $value['content']; ?></a></li>
        <?php endforeach; ?>
        <?php endif; ?>
    </ul>
</div>
</div>