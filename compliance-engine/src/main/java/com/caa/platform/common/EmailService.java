package com.caa.platform.common;

import org.springframework.beans.factory.annotation.Value;
import org.springframework.mail.SimpleMailMessage;
import org.springframework.mail.javamail.JavaMailSender;
import org.springframework.stereotype.Service;

/**
 * Michael, 2026-08-31 -- Password Reset feature, but deliberately built
 * generic rather than reset-specific -- this is the first real feature
 * in this project to actually send an email at all. Mail infrastructure
 * itself (spring-boot-starter-mail dependency, spring.mail.* config
 * pointed at Mailpit locally) already existed and was already fully
 * configured, just never used by anything until now -- this is only the
 * missing "actually call it" piece, not new transport setup.
 *
 * Plain text only for now (SimpleMailMessage) -- no HTML template
 * engine wired up yet. Fine for a reset link; a future, richer email
 * (e.g. the still-pending enrollment confirmation) would likely want
 * HTML and should build its own MimeMessage rather than stretch this
 * further, but the plain "from" config and JavaMailSender wiring below
 * would still be the right foundation to build on.
 */
@Service
public class EmailService {

    private final JavaMailSender mailSender;
    private final String fromAddress;

    public EmailService(JavaMailSender mailSender, @Value("${app.mail.from}") String fromAddress) {
        this.mailSender = mailSender;
        this.fromAddress = fromAddress;
    }

    public void send(String to, String subject, String body) {
        SimpleMailMessage message = new SimpleMailMessage();
        message.setFrom(fromAddress);
        message.setTo(to);
        message.setSubject(subject);
        message.setText(body);
        mailSender.send(message);
    }
}
