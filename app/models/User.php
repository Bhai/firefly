<?php

use Illuminate\Auth\Reminders\RemindableInterface;
use Illuminate\Auth\UserInterface;

/**
 * Class User
 */
class User extends Eloquent implements UserInterface, RemindableInterface
{

    public static $rules
        = ['email' => 'required|email|unique:users,email'];
    protected $fillable = ['email', 'activation', 'password', 'reset'];
    protected $softDelete = true;
    protected $table = 'users';
    protected $hidden = ['password'];

    /**
     * Get the user's accounts.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function accounts()
    {
        return $this->hasMany('Account');
    }

    public function piggybanks()
    {
        return $this->hasMany('Piggybank');
    }

    /**
     * Get the users settings.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function settings()
    {
        return $this->hasMany('Setting');
    }

    /**
     * Get the user's components.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function components()
    {
        return $this->hasMany('Component');
    }

    /**
     * Get the user's transactions.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function transactions()
    {
        return $this->hasMany('Transaction');
    }

    /**
     * Get the user's transfers.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function transfers()
    {
        return $this->hasMany('Transfer');
    }

    /**
     * Get the user's limits.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasManyThrough
     */
    public function limits()
    {
        return $this->hasManyThrough('Limit', 'Component');
    }

    /**
     * Get some kind of key.
     *
     * @return mixed
     */
    public function getAuthIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Get the user's password.
     *
     * @return string
     */
    public function getAuthPassword()
    {
        return $this->password;
    }

    /**
     * Get the user's email address.
     *
     * @return string
     */
    public function getReminderEmail()
    {
        return $this->email;
    }

    /**
     * Send the user a registration email.
     *
     * @return bool
     */
    public function sendRegistrationMail()
    {
        $data['url']
            = Config::get('app.url') . '/activate/' . $this->activation;
        $email = $this->email;
        Mail::send(
            ['email.register.html', 'email.register.text'], $data,
            function ($message) use ($email) {
                $message->to($email, $email)->subject('Welcome to Firefly!');
            }
        );

        return true;
    }

    /**
     * Give the user a new password and send it.
     *
     * @return bool
     */
    public function sendPasswordMail()
    {
        $data['password'] = Str::random(16);
        $email = $this->email;
        $this->password = Hash::make($data['password']);
        Mail::send(
            ['email.password.html', 'email.password.text'], $data,
            function ($message) use ($email) {
                $message->to($email, $email)->subject(
                    'Here\'s your new password!'
                );
            }
        );

        return true;
    }

    /**
     * Send the user instructions on how to reset his password.
     *
     * @return bool
     */
    public function sendResetMail()
    {
        $data['url'] = Config::get('app.url') . '/resetme/' . $this->reset;
        $email = $this->email;
        Mail::send(
            ['email.reset.html', 'email.reset.text'], $data,
            function ($message) use ($email) {
                $message->to($email, $email)->subject(
                    'Here\'s how to reset your password!'
                );
            }
        );

        return true;
    }

    /**
     * These fields must be converted to Carbon objects.
     *
     * @return array
     */
    public function getDates()
    {
        return ['created_at', 'updated_at', 'deleted_at'];
    }

}