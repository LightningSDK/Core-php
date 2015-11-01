<div class="row">
    <h2>Are you sure you want to unsubscribe from all MindPT mailing lists?</h2>
    <form action="/user" method="post">
        <input type="hidden" name="action" value="confirm-unsubscribe" />
        <input type="hidden" name="u" value="<?=\Lightning\Tools\Scrub::toHTML($user_token);?>" />
        <input type="submit" name="submit" value="Yes" class="button radius" />
        <a href="/" class="button radius">No</a>
    </form>
</div>
