package com.caa.platform.session;

import org.springframework.data.jpa.repository.JpaRepository;
import org.springframework.data.jpa.repository.JpaSpecificationExecutor;

import java.util.List;

public interface SessionRepository extends JpaRepository<Session, Long>, JpaSpecificationExecutor<Session> {
    List<Session> findBySchoolTypeAndPublished(SchoolType schoolType, boolean published);

    /**
     * Michael, 2026-08-29 -- performance audit finding: added so
     * calendar() (SessionController) can push its optional schoolType
     * filter down to a real, indexed query instead of loading every
     * session ever created via findAll() and filtering in memory.
     */
    List<Session> findBySchoolType(SchoolType schoolType);
}
