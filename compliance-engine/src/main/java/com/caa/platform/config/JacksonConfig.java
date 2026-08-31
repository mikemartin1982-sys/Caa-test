package com.caa.platform.config;

import com.fasterxml.jackson.datatype.hibernate6.Hibernate6Module;
import org.springframework.context.annotation.Bean;
import org.springframework.context.annotation.Configuration;

/**
 * Explicitly registers Hibernate6Module rather than relying on Spring
 * Boot's auto-detection of it on the classpath -- that auto-detection
 * didn't actually prevent "could not initialize proxy - no Session"
 * errors when Jackson serialized an entity with a populated-but-lazy
 * relation (see TestingSystem.trailer, Student.employerClient).
 *
 * FORCE_LAZY_LOADING disabled (the default, but pinned explicitly here
 * rather than trusted) means: an uninitialized lazy proxy serializes as
 * null instead of Jackson trying to trigger a DB fetch it has no open
 * session to actually perform.
 *
 * UNVERIFIED the same way CertificatePdfService is -- I can't compile-test
 * this against the real jackson-datatype-hibernate6 API from this
 * sandbox. If the TestingSystem/Trailer serialization still fails after
 * this, paste the error the same way as everything else tonight.
 */
@Configuration
public class JacksonConfig {

    @Bean
    public Hibernate6Module hibernate6Module() {
        Hibernate6Module module = new Hibernate6Module();
        module.disable(Hibernate6Module.Feature.FORCE_LAZY_LOADING);
        return module;
    }
}
