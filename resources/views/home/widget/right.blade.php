<div class="col-sm-3 sidebar">
    <div class="widget">

        <h3>RSS</h3>
        <p class="widget-body">
            <a title="定阅我吧" href="<?php echo route('blog.rss.index'); ?>"><img width="30" src="<?php echo loadStatic('/images/rss.png'); ?>"></a>
        </p>

    </div>

    <div class="widget">
    <h3 style="margin-top: 0px;">文章分类</h3>
        <div class="widget-body">
            <ul class="icons list-unstyled">
                <?php foreach($classifyInfo as $key => $value): ?>
                    <li><a href="<?php echo route('blog.category.list', ['categoryid' => $value['id']]); ?>"><i class="icon-angle-right"></i> <?php echo $value['name']; ?></a></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
    <div class="widget">

        <h3>标签</h3>
        <p class="widget-body">
            <?php foreach($tagsInfo as $key => $value): ?>
                <a href="<?php echo route('blog.tag.list', ['tagid' => $value['id']]); ?>"><span class="large label tag label-info"><?php echo $value['name']; ?></span></a>
            <?php endforeach; ?>
        </p>

    </div>
    <?php echo widget('Home.Common')->newComment(); ?>
    <?php echo widget('Home.Common')->servenDayHot(); ?>
    <?php echo widget('Home.Common')->totalHot(); ?>
    <?php echo widget('Home.Common')->tongJi(); ?>

    
</div>