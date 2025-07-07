<?php
declare(strict_types=1);

namespace TwoQuick\Api\Forms;
use Assert\Assert;
use Assert\AssertionFailedException;
use Assert\InvalidArgumentException;

class RegisterForm extends FormBase
{
    public $user_type;
    public $name;
    public $email;
    public $phone;
    public $company;
    public $inn;
    public $password;
    public $confirm_password;

    /**
     * @return bool
     */
    public function validate(): bool
    {
        try {
            $assertLazy = Assert::lazy();
            $assertLazy
                ->that($this->name, 'name', 'Имя не должно быть пустым')->notEmpty()
                ->that($this->email, 'email', 'Email не должен быть пустым')->notEmpty()
                ->that($this->email, 'email', 'Email не валидный')->email()
                ->that($this->phone, 'phone', 'Телефон не должен быть пустым')->notEmpty()
                ->that($this->password, 'password', 'Пароль не должен быть пустым')->notEmpty()
                ->that($this->confirm_password, 'password', 'Подтверждение пароля не должен быть пустым')->notEmpty();
            if($this->user_type=='L'){
                $assertLazy
                    ->that($this->company, 'company', 'Огранизация не должена быть пустой')->notEmpty()
                    ->that($this->inn, 'inn', 'ИНН не должен быть пустым')->notEmpty();
            }
            $assertLazy->verifyNow();

            return true;
        } catch (AssertionFailedException $ex) {
            /** @var InvalidArgumentException $error */
            foreach ($ex->getErrorExceptions() as $error) {
                $errors[$error->getPropertyPath()] =$error->getMessage();
            }
            $this->setError($errors);
            return false;
        }
    }
}
