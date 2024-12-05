<?php

class Service_Mail
{
    public function __construct()
    {
        if (getenv('PREVARISC_MAIL_ENABLED') && 1 == getenv('PREVARISC_MAIL_ENABLED')) {
            $transport = null;
            $config = [];
            if ('' !== getenv('PREVARISC_MAIL_PORT')) {
                $config['port'] = getenv('PREVARISC_MAIL_PORT');
            }

            if ('' !== getenv('PREVARISC_MAIL_USERNAME')
                && '' !== getenv('PREVARISC_MAIL_PASSWORD')) {
                $config['auth'] = 'login';
                $config['username'] = getenv('PREVARISC_MAIL_USERNAME');
                $config['password'] = getenv('PREVARISC_MAIL_PASSWORD');
            }

            switch (getenv('PREVARISC_MAIL_TRANSPORT')) {
                case 'smtp':
                    $transport = new Zend_Mail_Transport_Smtp(
                        getenv('PREVARISC_MAIL_HOST'),
                        $config
                    );

                    break;

                case 'sendmail':
                case 'mail':
                    $transport = new Zend_Mail_Transport_Sendmail([
                        getenv('PREVARISC_MAIL_HOST'),
                        $config,
                    ]);

                    break;

                default:
                    $transport = null;
            }

            if (null !== $transport) {
                Zend_Mail::setDefaultTransport($transport);
            }

            if ('' !== getenv('PREVARISC_MAIL_SENDER')
                && '' !== getenv('PREVARISC_MAIL_SENDER_NAME')) {
                Zend_Mail::setDefaultFrom(
                    getenv('PREVARISC_MAIL_SENDER'),
                    getenv('PREVARISC_MAIL_SENDER_NAME')
                );
            } elseif ('' !== getenv('PREVARISC_MAIL_SENDER')) {
                Zend_Mail::setDefaultFrom(getenv('PREVARISC_MAIL_SENDER'));
            }
        }
    }

    public function sendAlerteMail($objet, $message, $destinataires)
    {
        return $this->sendMail($message, $objet, null, $destinataires, true);
    }

    /**
     * @param mixed      $message
     * @param null|mixed $objet
     * @param null|mixed $to
     * @param null|mixed $bcc
     * @param mixed      $isHTML
     *
     * @return bool|Zend_Mail_Protocol_Exception|Zend_Mail_Transport_Exception
     */
    public function sendMail($message, $objet = null, $to = null, $bcc = null, $isHTML = false)
    {
        $sent = true;
        if (getenv('PREVARISC_MAIL_ENABLED') && 1 == getenv('PREVARISC_MAIL_ENABLED')) {
            $mail = new Zend_Mail('utf-8');

            if ($isHTML) {
                $mail->setBodyHtml($message);
            } else {
                $mail->setBodyText($message);
            }

            if ($objet) {
                $mail->setSubject($objet);
            }

            if ($to) {
                if (is_array($to)) {
                    foreach ($to as $dest) {
                        $mail->addTo($dest);
                    }
                } else {
                    $mail->addTo($to);
                }
            }

            if ($bcc) {
                if (is_array($bcc)) {
                    foreach ($bcc as $cc) {
                        $mail->addBcc($cc);
                    }
                } else {
                    $mail->addBcc($bcc);
                }
            }

            try {
                $mail = $mail->send();
            } catch (Zend_Mail_Transport_Exception $zmte) {
                $sent = $zmte;
            } catch (Zend_Mail_Protocol_Exception $zmpe) {
                $sent = $zmpe;
            }
        } else {
            $sent = false;
        }

        return $sent;
    }
}
