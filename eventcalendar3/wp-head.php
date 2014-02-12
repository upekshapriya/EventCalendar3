<?php global $ec3,$month,$month_abbrev ?>

	<!-- Added by Event-Calendar plugin. Version <?php echo $ec3->version; ?> -->
	<script type='text/javascript' src='<?php echo $ec3->myfiles; ?>/xmlhttprequest.js'></script>
	<script type='text/javascript' src='<?php echo $ec3->myfiles; ?>/ec3.js'></script>
	<script type='text/javascript'><!--
	ec3.start_of_week=<?php echo intval( get_option('start_of_week') ); ?>;
	ec3.month_of_year=new Array('<?php echo implode("','",$month); ?>');
	ec3.month_abbrev=new Array('<?php echo implode("','",$month_abbrev); ?>');
	ec3.myfiles='<?php echo $ec3->myfiles; ?>';
	ec3.home='<?php echo get_option('home'); ?>';
	ec3.viewpostsfor="<?php echo __('View posts for %1$s %2$s'); ?>";
	// --></script>

<?php if(!$ec3->nocss): ?>
	<style type='text/css' media='screen'>
	@import url(<?php echo $ec3->myfiles; ?>/ec3.css);
	.ec3_ec{ background-image:url(<?php echo $ec3->myfiles; ?>/ec.png) !IMPORTANT }
	#ec3_shadow0{ background-image:url(<?php echo $ec3->myfiles; ?>/shadow0.png) !IMPORTANT }
	#ec3_shadow1{ background-image:url(<?php echo $ec3->myfiles; ?>/shadow1.png) !IMPORTANT }
	#ec3_shadow2{ background-image:url(<?php echo $ec3->myfiles; ?>/shadow2.png) !IMPORTANT }
	</style>

<!--[if IE]>
	<style type='text/css' media='screen'>
	.ec3_ec {
	 background-image:none;
	 filter:progid:DXImageTransform.Microsoft.AlphaImageLoader(src='<?php echo $ec3->myfiles; ?>/ec.png');
	}
	#ec3_shadow0, ec3_shadow1, ec3_shadow2{ background-image:none }
	#ec3_shadow0 div{ filter:progid:DXImageTransform.Microsoft.AlphaImageLoader(src='<?php echo $ec3->myfiles; ?>/shadow0.png',sizingMethod='scale') }
	#ec3_shadow1    { filter:progid:DXImageTransform.Microsoft.AlphaImageLoader(src='<?php echo $ec3->myfiles; ?>/shadow1.png',sizingMethod='crop') }
	#ec3_shadow2 div{ filter:progid:DXImageTransform.Microsoft.AlphaImageLoader(src='<?php echo $ec3->myfiles; ?>/shadow2.png',sizingMethod='scale') }
	</style>
<![endif]-->

<?php endif ?>
