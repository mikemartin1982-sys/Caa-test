package com.caa.platform;

import org.springframework.boot.SpringApplication;
import org.springframework.boot.autoconfigure.SpringBootApplication;
import org.springframework.scheduling.annotation.EnableScheduling;

/**
 * Michael, 2026-09-01 -- Client Auto-Notify feature. @EnableScheduling
 * added here -- the first @Scheduled task in this project
 * (QboPaymentPollingService) needs this present somewhere, or it's
 * simply never invoked at all, silently, with no error or log.
 */
@SpringBootApplication
@EnableScheduling
public class ComplianceEngineApplication {

    public static void main(String[] args) {
        SpringApplication.run(ComplianceEngineApplication.class, args);
    }
}
