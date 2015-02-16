<?php
// Include 3rd party libraries and config file
require_once 'PHPMailer/PHPMailerAutoload.php';
require_once 'config-site.inc.php'; // @uses ON_TEST_SERVER
require_once 'mailconf.inc.php'; // TODO: adjust values in that file!

function SendMail($p_action, $p_name, $p_anzZu, $p_anzAb, $p_next) {
	global $emailFrom, $rootUrl, $teamNameShort;
	if (@ON_TEST_SERVER OR !$p_action) { return; }

	$mailSender = "training-{$emailFrom}@uwr1.de";
	$mailFrom = "\"[{$teamNameShort}] Trainingsseite\" <{$mailSender}>";
	$mailReturnPath = "<{$mailSender}>";
	$mailHeader = "From: {$mailFrom}\r\n"
				. "Sender: {$mailSender}\r\n"
				. "Return-Path: {$mailReturnPath}\r\n";

	if ('reset' == $p_action) {
		$subject	= 'Training - Reset';
		mail_SMTP('training@uwr1.de', 'Training - Reset', 'k/T');
		return;
	}

	$toArray = $GLOBALS['spieler'];
	$anzahlGesamt = $p_anzZu + $p_anzAb;
	foreach ($toArray as $spielerId => $spieler) {
		// user will jede $freq-te mail
		if (0 < $spieler['freq'] AND 0 == $anzahlGesamt % $spieler['freq']) {
			$toArray[$spielerId]['nixgesagt'] = in_array($spieler['name'], $GLOBALS['nixgesagt']);
		}
		// user will die ersten $freq mails
		elseif (0 > $spieler['freq'] AND $anzahlGesamt <= abs($spieler['freq'])) {
			$toArray[$spielerId]['nixgesagt'] = in_array($spieler['name'], $GLOBALS['nixgesagt']);
		}
		// user will keine mails
		else {
			unset($toArray[$spielerId]);
		}

		// user will keine mail wenn er sich gerade selbst an-/abgemeldet hat
		if (($p_name == $spieler['name']) AND @$spieler['keineSelbstMail']) {
			unset($toArray[$spielerId]);
		}

		// user will keine mail wenn er sich bereits an-/abgemeldet hat
		if (@$spieler['keineMailsNachMeldung'] AND !$toArray[$spielerId]['nixgesagt']) {
			unset($toArray[$spielerId]);
		}
	}

	$meldungStatus = '';
	if ('add' == $p_action) {
		$meldungStatus = 'zum Training angemeldet.';
		$betreffStatus = 'Zusage';
	}
	if ('remove' == $p_action) {
		$meldungStatus = 'vom Training abgemeldet.';
		$betreffStatus = 'Absage';
	}
	$subject = "[UWR] Training: {$betreffStatus} von {$p_name}"
		. ' ('
		. "+{$p_anzZu}/-{$p_anzAb}"
		. ' - '
		. "{$p_next['wtag']}, ".date('d.m.', $p_next['datum']).", {$p_next['zeit']} Uhr";
/* TODO BA: integrate this into subject -> hook + plugin
	if ('Zapfendorf' == $p_next['ort']) {
		$subject .= ', ' . GetWasserTemp();
	}
*/
	$subject .=  ')';
	$trainingsUrl = $rootUrl;
	$meldeUrl     = $trainingsUrl.'training.php?text=';
	$wassertemp = '';
/* TODO BA: move to hook + plugin
	$wassertemp = '';
	if ('Zapfendorf' == $p_next['ort']) {
		$wassertemp = 'Wassertemperatur: ' . GetWasserTemp()."\n\n";
	}
*/
	$zwischenstand = "Zwischenstand: {$p_anzZu} Zusagen, {$p_anzAb} Absagen.\n"
				. "Den aktuellen Stand findest Du hier: {$trainingsUrl}\n\n";
	$neu = "Funktionen:\n"
		. "+ Einstellbare E-Mail Häufigkeit (mir sagen wie gewünscht):\n"
		. "  - Wahlweise nur die ersten x Mails oder jede x-te Mail bekommen.\n"
		. "  - Wahlweise keine Mail für die eigene Meldung bekommen.\n"
		. "  - Wahlweise keine Mails mehr bekommen nachdem man sich gemeldet hat.\n"
		. "+ Direkte An-/Abmeldung aus den Mails\n"
		. "+ Ein längerer Text ist möglich, das erste Wort wird als Name erkannt,\n"
		. "  z.B.: \"Flo muss schlafen\"\n\n";
	$ps = '';

//$to = 'training-test@uwr1.de';
//$subject .= ' - testing -';
	foreach ($toArray as $empf) {
		$anrede = "Hallo {$empf['name']},\n\n";
		$meldung = (($p_name == $empf['name']) ? 'Du hast dich' : "{$p_name} hat sich");
		$meldung .= " gerade {$meldungStatus}\n\n";
		$aufforderung = '';
		if ($empf['nixgesagt']) {
			$anmeldeUrl = $meldeUrl.$empf['name'].'&zusage=1';
			$abmeldeUrl = $meldeUrl.$empf['name'].'&absage=1';
			$aufforderung = "Kommst Du am {$p_next['wtag']}, ".date('d.m.', $p_next['datum'])." auch?\n"
					. "+ Ja:    {$anmeldeUrl}\n"
					. "- Nein:  {$abmeldeUrl}\n\n";
		}

		if (@ON_TEST_SERVER) {
/*
			print "mail_SMTP({$empf['email']},
					{$subject},
					{$anrede}.{$meldung}.{$aufforderung}.{$zwischenstand}.{$neu}.{$ps}
			);";
			print "<br>";
			print '$empf[\'name\']='.($empf['nixgesagt']?1:0);
			print "<br><br>";
*/
		} else {
			mail_SMTP($empf['email'],
				$subject,
				$anrede.$meldung.$aufforderung.$wassertemp.$zwischenstand.$neu.$ps
			);
		}
	}
}

function mail_SMTP($to, $subject, $msg) {
	if (!$to) return false;
	if (!$subject) return false;
	if (!$msg) return false;

	$mail = new PHPMailer;

	//$mail->SMTPDebug = 3;                               // Enable verbose debug output

	$mail->isSMTP();                                      // Set mailer to use SMTP
	$mail->Host = MAILER_SMTP_HOST;  // Specify main and backup SMTP servers
	$mail->SMTPAuth = true;                               // Enable SMTP authentication
	$mail->Username = MAILER_USER;                 // SMTP username
	$mail->Password = MAILER_PASSWORD;                           // SMTP password
	//$mail->SMTPSecure = 'ssl';                            // Enable TLS encryption, `ssl` also accepted
	//$mail->Port = 587;                                    // TCP port to connect to
	$mail->Port = 25;                                    // TCP port to connect to

	$mail->From = MAILER_FROM;
	$mail->FromName = MAILER_NAME;
	$mail->addAddress($to);               // Name is optional
	$mail->addReplyTo(MAILER_FROM, MAILER_NAME);
	// Add ReturnPath?
	// Add Sender?

	$mail->WordWrap = 78;                                 // Set word wrap to 78 characters

	$mail->Subject = $subject;
	$mail->Body    = $msg;
	//$mail->AltBody = 'This is the body in plain text for non-HTML mail clients';

	return $mail->send();
}
