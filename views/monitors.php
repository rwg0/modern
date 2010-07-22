<?php
require_once("../../../includes/config.php");
require_once("../includes/config.php");
require_once("../../../includes/database.php");
require_once("../../../includes/functions.php");
$mid = $_REQUEST['mid'];
$bandwidth = $_COOKIE['zmBandwidth'];
if ( isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] == 'on' )
{
    $protocol = 'https';
}
else
{
    $protocol = 'http';
}
define( "ZM_BASE_URL", $protocol.'://'.$_SERVER['HTTP_HOST'] );

if ($mid) {
 $monitors = dbFetchAll( "select Id, Name, Width, Height from Monitors where Id = " . $mid . " order by Sequence asc" );
} else {
 $monitors = dbFetchAll( "select Id, Name, Width, Height from Monitors order by Sequence asc" );
}
$displayMonitors = array();
for ( $i = 0; $i < count($monitors); $i++ )
{
    if ( !visibleMonitor( $monitors[$i]['Id'] ) )
    {
        continue;
    }
    if ( $group && !empty($groupIds) && !array_key_exists( $monitors[$i]['Id'], $groupIds ) )
    {
        continue;
    }
    $monitors[$i]['zmc'] = zmcStatus( $monitors[$i] );
    $monitors[$i]['zma'] = zmaStatus( $monitors[$i] );
    $displayMonitors[] = $monitors[$i];
}

if (!defined(ZM_WEB_DEFAULT_SCALE)) {
 $scale = 40;
} else {
 $scale = ZM_WEB_DEFAULT_SCALE;
}
foreach( $displayMonitors as $monitor ){
    if ( !$monitor['zmc'] )
        $dclass = "errorText";
    else
    {
        if ( !$monitor['zma'] )
            $dclass = "warnText";
        else
            $dclass = "infoText";
    }
    if ( $monitor['Function'] == 'None' )
        $fclass = "errorText";
    elseif ( $monitor['Function'] == 'Monitor' )
        $fclass = "warnText";
    else
        $fclass = "infoText";
    if ( !$monitor['Enabled'] )
        $fclass .= " disabledText";
 if (($bandwidth == 'low' || $bandwidth == "medium" || $bandwidth == "") || !($bandwidth)) {
  $streamSrc = getStreamSrc( array( "mode=single", "monitor=".$monitor['Id'], "scale=".$scale ) );
 } elseif ($bandwidth == 'high') {
   if ( ZM_STREAM_METHOD == 'mpeg' && ZM_MPEG_LIVE_FORMAT ) {
    $streamMode = "mpeg";
    $streamSrc = getStreamSrc( array( "mode=".$streamMode, "monitor=".$monitor['Id'], "scale=".$scale, "bitrate=".ZM_WEB_VIDEO_BITRATE, "maxfps=".ZM_WEB_VIDEO_MAXFPS, "format=".ZM_MPEG_LIVE_FORMAT, "buffer=".$monitor['StreamReplayBuffer'] ) );
} elseif ( canStream() ) {
    $streamMode = "jpeg";
    $streamSrc = getStreamSrc( array( "mode=".$streamMode, "monitor=".$monitor['Id'], "scale=".$scale, "maxfps=".ZM_WEB_VIDEO_MAXFPS, "buffer=".$monitor['StreamReplayBuffer'] ) );
  }
 }
 $width = ($monitor['Width'] * ('.' . $scale) + 20);
?>
<li id="monitor_<?php echo $monitor['Id'] ?>" style="width:<?php echo $width ?>px;">
 <div class="mon_header">
  <h3><?php echo $monitor['Name'] ?></h3>
 </div>
 <div class="mon">
 <a href="?view=events&page=1&filter[terms][0][attr]=MonitorId&filter[terms][0][op]==&filter[terms][0][val]=<?php echo $monitor['Id'] ?>" >
  <?php outputImageStill( "liveStream", $streamSrc, reScale( $monitor['Width'], $scale ), reScale( $monitor['Height'], $scale ), $monitor['Name'] ); ?>
 </a>
 </div>
 <div class="monfooter">
 <div class="spinner"></div>
 </div>
 <a rel="monitor" href="?view=full&amp;mid=<?= $monitor['Id']; ?>&amp;scale=<?= $scale ?>" title="<?= $monitor['Name']; ?>">View Full</a>
</li>
<?php } ?>
