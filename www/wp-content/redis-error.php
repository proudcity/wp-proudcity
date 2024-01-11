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


