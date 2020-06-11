<div class="row">
    <?php

    use Lightning\Tools\Form;
    use Lightning\View\Field;

    if (empty($action) || $action == 'join' || $action == 'register'): ?>
        <div class="small-12 <?= empty($action) ? 'medium-6' : 'medium-6 medium-offset-3'; ?> column">
            <fieldset>
                <legend>Create a username and password</legend>
                <form action="/user" method="post" id="register" data-abide>
                    <?= Form::renderTokenInput(); ?>
                    <div data-abide-error class="alert callout" style="display: none;">
                        <p><i class="fi-alert"></i> There are some errors in your form.</p>
                    </div>
                    <div>
                        <label>Your Name:
                            <input type="text" name="name" id='name' value="<?=Field::defaultValue('name');?>" required />
                            <span class="form-error">Please enter your name.</span>
                        </label>
                    </div>
                    <div>
                        <label>Your Email:
                            <input type="email" name="email" id='email' value="<?=Field::defaultValue('email');?>" required />
                            <span class="form-error">Please enter your email.</span>
                        </label>
                    </div>
                    <div>
                        <label>Create a Password:
                            <input type="password" name="password" id='password' value="" pattern="[a-zA-Z0-9]+" required />
                            <span class="form-error">The password must be at least 8 characters and contain at least one number.</span>
                        </label>
                    </div>
                    <div>
                        <label>Confirm your Password:
                            <input type="password" name="password2" id='password2' value="" data-equalto="password" required />
                            <span class="form-error">Please enter the same password.</span>
                        </label>
                    </div>
                    <input type="hidden" name="action" value="register" />
                    <input type="hidden" name="redirect" value="<?=!empty($redirect) ? $redirect : '';?>" />
                    <input type="submit" value="Register" class="button" />
                </form>
            </fieldset>
        </div>
    <?php endif; ?>

    <?php if (empty($action) || $action == "login"): ?>
        <div class="small-12 <?= empty($action) ? 'medium-6' : 'medium-6 medium-offset-3'; ?> column">
            <fieldset>
                <legend>Log in with your email and password</legend>
                <form action="/user" method="post" id="login" data-abide>
                    <?= Form::renderTokenInput(); ?>
                    <div data-abide-error class="alert callout" style="display: none;">
                        <p><i class="fi-alert"></i> There are some errors in your form.</p>
                    </div>
                    <div>
                        <label>Your Email:
                            <input type="email" name="email" id='email' value="<?=Field::defaultValue('email');?>" required />
                            <span class="form-error">Please enter your email.</span>
                        </label>
                    </div>
                    <div>
                        <label>Your password:
                            <input type="password" name="password" id='password' value="" required />
                            <span class="form-error">Please enter your password.</span>
                        </label>
                    </div>
                    <input type="hidden" name="action" value="login" />
                    <input type="hidden" name="redirect" value="<?=!empty($redirect) ? $redirect : '';?>" />
                    <input class="button" type="submit" value="Log In"/>
                    <a href='/user?action=reset' class="right">Forgot your password?</a>
                </form>
            </fieldset>
        </div>
    <?php endif; ?>
    <?php if (!empty($action) && $action == 'reset'): ?>
        <div class="small-12 medium-6 medium-offset-3">
            <fieldset>
                <legend>Forgot your password?</legend>
                <form action="/user" method="post" data-abide>
                    <?= Form::renderTokenInput(); ?>
                    <div data-abide-error class="alert callout" style="display: none;">
                        <p><i class="fi-alert"></i> There are some errors in your form.</p>
                    </div>
                    <div>
                        <label>Your Email:
                            <input type="email" name="email" id='email' value="<?=Field::defaultValue('email');?>" required />
                            <span class="form-error">Please enter your email.</span>
                        </label>
                    </div>
                    <input type="hidden" name="action" value="reset" />
                    <input type="hidden" name="redirect" value="<?=!empty($redirect) ? $redirect : '';?>" />
                    <input type="submit" value="Reset" class="button" />
                </form>
            </fieldset>
        </div>
    <?php endif; ?>
    <?php if (!empty($action) && $action == "set_password"): ?>
        <fieldset>
            <legend>Set your new password:</legend>
            <form action="/user" method="post" data-abide>
                <?= Form::renderTokenInput(); ?>
                <div data-abide-error class="alert callout" style="display: none;">
                    <p><i class="fi-alert"></i> There are some errors in your form.</p>
                </div>
                <div>
                    <label>Create a Password:
                        <input type="password" name="password" id='password' value="" pattern="[a-zA-Z0-9]+" required />
                        <span class="form-error">The password must be at least 8 characters and contain at least one number.</span>
                    </label>
                </div>
                <div>
                    <label>Confirm your Password:
                        <input type="password" name="password2" id='password2' value="" data-equalto="password" required />
                        <span class="form-error">Please enter the same password.</span>
                    </label>
                </div>
                <input type="hidden" name="key" value="<?= $key; ?>" />
                <input type="hidden" name="action" value="set_password" />
                <input type="submit" value="Set Password" class="button" />
            </form>
        </fieldset>
    <?php endif; ?>
</div>
