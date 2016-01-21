<div class="widget">
<h3 style="margin-top: 0px;">七天热傍</h3>
<div class="widget-body">
    <ul class="icons list-unstyled">
        <?php if(isset($list) and is_array($list)): ?>
        <?php $i = 1; ?>
        <?php foreach($list as $key => $value): ?>
            <li><?php echo $i; ?>、<a href="<?php echo route('blog.index.detail', ['id' => $value['id']]); ?>"><i class="icon-angle-right"></i> <?php echo $value['title']; ?></a></li>
            <?php $i++ ?>
        <?php endforeach; ?>
        <?php endif; ?>
    </ul>
</div>
</div>