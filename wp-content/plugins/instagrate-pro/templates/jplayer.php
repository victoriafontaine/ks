<script type="text/javascript">
	//<![CDATA[
	jQuery(document).ready(function($){
		var videoHeight = "auto";
		if ($.browser.mozilla) {
			var realWidth = $("#jp_container_<?php echo $count; ?>").parent().width();
			if (realWidth > <?php echo $dim; ?>) realWidth = <?php echo $dim; ?>;
			videoHeight = realWidth + "px";
		}
		$("#igp-jplayer-<?php echo $count; ?>").jPlayer({
			ready: function () {
				$(this).jPlayer("setMedia", {
					m4v: "<?php echo $src; ?>",
					poster: "<?php echo $poster; ?>"
				});
			},
			play: function() { // To avoid multiple jPlayers playing together.
				$(this).jPlayer("pauseOthers");
			},
			swfPath: "<?php echo $jsurl; ?>",
			supplied: "m4v",
			size: {
				width: "100%",
				height: videoHeight,
				cssClass: "jp-video-<?php echo $dim; ?>p"
			},
			cssSelectorAncestor: "#jp_container_<?php echo $count ; ?>"
		});
	});
	//]]>
</script>
<div id="jp_container_<?php echo $count; ?>" class="jp-video jp-video-<?php echo $dim; ?>p">
	<div id="igp-jplayer-<?php echo $count; ?>" class="jp-jplayer"></div>
	<div class="jp-gui">
		<div class="jp-video-play" style="display: block;">
			<a class="jp-video-play-icon jp-play" tabindex="1" href="javascript:;">play</a>
		</div>
		<div class="jp-interface">
			<div class="jp-controls-holder">
				<a href="javascript:;" class="jp-play" tabindex="1">play</a>
				<a href="javascript:;" class="jp-pause" tabindex="1">pause</a>
				<span class="separator sep-1"></span>
				<div class="jp-progress">
					<div class="jp-seek-bar">
						<div class="jp-play-bar"><span></span></div>
					</div>
				</div>
				<div class="jp-current-time"></div>
				<span class="time-sep">/</span>
				<div class="jp-duration"></div>
				<span class="separator sep-2"></span>
				<a href="javascript:;" class="jp-mute" tabindex="1" title="mute">mute</a>
				<a href="javascript:;" class="jp-unmute" tabindex="1" title="unmute">unmute</a>
				<div class="jp-volume-bar">
					<div class="jp-volume-bar-value"><span class="handle"></span></div>
				</div>
				<span class="separator sep-2"></span>
				<a href="javascript:;" class="jp-full-screen" tabindex="1" title="full screen">full screen</a>
				<a href="javascript:;" class="jp-restore-screen" tabindex="1" title="restore screen">restore screen</a>
				<a href="javascript:;" class="jp-repeat" tabindex="1" title="repeat">repeat</a>
				<a href="javascript:;" class="jp-repeat-off" tabindex="1" title="repeat off">repeat off</a>
			</div>
		</div>
	</div>
	<div class="jp-no-solution">
		<span>Update Required</span>
		To play the media you will need to either update your browser to a recent version or update your <a href="http://get.adobe.com/flashplayer/" target="_blank">Flash plugin</a>.
	</div>
</div>
 