<div class="clearingfix"></div>
<div id="tagcloud">
    <h5><?php print $cloud_title; ?></h5>
    <?php if (count($cloud_items)): ?>
        <?php foreach ($cloud_items as $key => $item): ?>
            <span><a href="<?php echo url::base().'reports/?c='.(int)$item['id'] ?>" style="<?php echo $item['css']; ?>"><?php echo $item['category_title']; ?></a></span>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
<div class="clearingfix"></div>