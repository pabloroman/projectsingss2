
<div class="clearfix"></div>
<div class="header-navigation">
    <div class="">
        <a href="javascript:void(0);" class="mobile-menu"><span></span> Menu</a>
        <?php if(Request::segment(2) =='talent' && Request::segment(3) != 'network'): ?>
        <?php echo ___getTalentMenu('talent-sub-top-after-login','talent-top-after-login','%s %s','active',false,false); ?>

        <?php elseif(Request::segment(2) =='talent' && Request::segment(3) == 'network'): ?>
        <?php echo ___getTalentMenu('talent-sub-top-after-login','talent-network','%s %s','active',false,false); ?>

        <?php elseif(Request::segment(2) == 'network'): ?>
        <?php echo ___getTalentMenu('talent-sub-top-after-login','talent-network','%s %s','active',false,false); ?>

        <?php else: ?>
        <?php echo ___getTalentMenu('talent-sub-top-after-login','talent-top-after-login','%s %s','active',false,false); ?>

        <?php endif; ?>
    </div>
</div>