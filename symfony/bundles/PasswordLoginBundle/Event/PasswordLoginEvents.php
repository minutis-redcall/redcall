<?php

namespace Bundles\PasswordLoginBundle\Event;

class PasswordLoginEvents
{
    const PRE_REGISTER         = 'password_login.events.pre_register';
    const POST_REGISTER        = 'password_login.events.post_register';
    const PRE_VERIFY_EMAIL     = 'password_login.events.pre_verify_email';
    const POST_VERIFY_EMAIL    = 'password_login.events.post_verify_email';
    const PRE_EDIT_PROFILE     = 'password_login.events.pre_edit_profile';
    const POST_EDIT_PROFILE    = 'password_login.events.post_edit_profile';
    const PRE_CHANGE_PASSWORD  = 'password_login.events.pre_change_password';
    const POST_CHANGE_PASSWORD = 'password_login.events.post_change_password';
}