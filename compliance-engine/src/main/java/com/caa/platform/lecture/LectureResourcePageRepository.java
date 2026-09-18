package com.caa.platform.lecture;

import org.springframework.data.jpa.repository.JpaRepository;

import java.util.List;
import java.util.Optional;

public interface LectureResourcePageRepository extends JpaRepository<LectureResourcePage, Long> {
    List<LectureResourcePage> findAllByOrderByOrderIndexAsc();
    Optional<LectureResourcePage> findBySlug(String slug);
}
