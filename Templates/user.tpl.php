<script language="javascript">

$(document).ready(function(){
	$("#register").validate({
        rules: {
            password: 
            {
              required: true
            },
            password2: {
                equalTo: "#password"
            }
        }
    });
});

</script>
<? if ( $l_page == "" || $l_page == "join" || $l_page == "register"): ?>
  <h2>Create a username and password</h2>
  <form action="user.php" method="post" id="register">

  <table>
  <tr><td>Email:</td><td><input type='text' name='email' class="required email" /></td></tr>
  <tr><td>Password:</td><td><input type='password' name='password' id='password' class="required" /></td></tr>
  <tr><td>Confirm Password:</td><td><input type='password' name='password2' class="required" /></td></tr>
  </table>
  <input type="hidden" name="action" value="register" />
  <input type="hidden" name="redirect" value="<?=$redirect?>" />
  <input type="submit" name="submit" value="Register" />

  </form>

  <br /><br />
<? endif; ?>

<? if ( $l_page == "" || $l_page == "login"): ?>

  <h2>Log In with your email and password.</h2>

  <form action="user.php" method="post" id="register">

  <table>
  <tr><td>Email:</td><td><input type='text' name='email' /></td></tr>
  <tr><td>Password:</td><td><input type='password' name='password' /></td></tr>
  </table>
  <input type="hidden" name="action" value="login" />
  <input type="hidden" name="redirect" value="<?=$redirect?>" />
  <input type="submit" name="submit" value="Log In" />
  <a href='user.php?p=reset'>Forgot your password?</a>
  </form>


  <br /><br />
<? endif; ?>
<? if ( $l_page == "reset"): ?>
  <h2>Forgot your password?</h2>

  <form action="user.php" method="post">

  <table>
  <tr><td>Enter your email address:</td><td><input type='text' name='email' /></td></tr>
  </table>
  <input type="hidden" name="action" value="reset" />
  <input type="hidden" name="redirect" value="<?=$redirect?>" />
  <input type="submit" name="submit" value="Log In" />

  </form>
<? endif; ?>