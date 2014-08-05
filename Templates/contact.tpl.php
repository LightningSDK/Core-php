<h1>Contact</h1>

<? if ($sent == "true"): ?>
    Your message has been sent
<? else: ?>

    <script language="javascript">
        $(document).ready(function(){
            $('#contact_form').validate();
        });
    </script>

    <form action="contact.php<? if ($to_user_id > 0): ?>?to_user_id=<?=$to_user_id?><? endif; ?>" method="post" id="contact_form">

        <? if ( $to_user_id > 0): ?>
            Contact: <?=$aa->print_name($to_user['user_first'], $to_user['user_last'], $to_user['user_preferences'])?><br />
            <input type="hidden" name="to_user_id" value="<?=$to_user_id?>" />
        <? elseif ($contact_lawyer): ?>
            Contact <?=$lawyer['lawyer_firm']?>:<br /><br />
            <input type="hidden" name="lawyer" value="<?=$lawyer['lawyer_id']?>" />
        <? else: ?>
            Contact Us:<br />

            <strong>ATTENTION</strong>
            <p>
                We are not a law firm and do not provide legal advice. Unfortunately we do not have the resources to help everybody with their cases personally. If you have a complaint against a police officer or other public official, please submit it <a href="/Source/Pages/report.php">on the complaint page</a>. If you would like to contact us for any other reason, please use the information below.
            </p>

            <input type="hidden" name="to_user_id" value="0" />
        <? endif; ?>
        <input type="hidden" name="action" value="send_message" />

        <? if ( $to_user_id > 0):?>

        <? else: ?>
            Your Name:<br />
            <input type="text" name="name" id='name' value="" class="required" /><br />
            <? if ( $contact_lawyer): ?>
                Your Phone Number:<br />
                <input type="text" name="phone" id='my_phone' value="" class="required" /><br />
                <input type="hidden" name="lawyer" id='lawyer' value="<?=$lawyer['lawyer_id']?>" />
            <? endif; ?>
        <? endif; ?>
        Your Email:<br />
        <input type="text" name="email" id='my_email' value="<?=$postedemail?>" class="required email" /><br />
        Your message:<br />
        <textarea name="message" cols="70" rows="20"><?=$postedmessage?></textarea><br />
        <?=\Lightning\Tools\ReCaptcha::render()?>
        <br />
        <input type="Submit" name="Submit" value="Send Message" class="button" />
    </form>
<? endif; ?>
