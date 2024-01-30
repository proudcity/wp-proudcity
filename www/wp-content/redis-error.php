<h2>An error was encountered</h2>

<p>Looks like we've encountered a minor hiccup.</p>

<p>Refresh your browser and this should resolve. If the problem persists please get in touch with support.</p>

<p>The browser will refresh in <span id="countdown"></span></p>

<script type="text/javascript">
(function countdown(remaining) {
    if(remaining <= 0)
        location.reload(true);
    document.getElementById('countdown').innerHTML = remaining;
    setTimeout(function(){ countdown(remaining - 1); }, 1000);
})(5); // 5 seconds
</script>

<?php
	$slack_key = getenv( 'PROUD_REDIS_SLACK_KEY' );
	$url = $_SERVER['HTTP_HOST'];

	$message_content = 'Redis issue on ' . $url . ' at' . date( 'F d Y G:H:s e' );

	$curl = curl_init( $slack_key );

	$message = array( 'payload' => json_encode( array( 'text' => $message_content ) ) );

	curl_setopt( $curl, CURLOPT_SSL_VERIFYPEER, false );
	curl_setopt( $curl, CURLOPT_POST, true );
	curl_setopt( $curl, CURLOPT_POSTFIELDS, $message );

	$result = curl_exec( $curl );
	curl_close( $curl );
?>
