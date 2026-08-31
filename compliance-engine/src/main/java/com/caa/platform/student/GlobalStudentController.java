package com.caa.platform.student;

import org.springframework.http.ResponseEntity;
import org.springframework.web.bind.annotation.GetMapping;
import org.springframework.web.bind.annotation.PathVariable;
import org.springframework.web.bind.annotation.RestController;

/**
 * Michael, 2026-08-30 -- Lecture Certificate Upload feature. Every
 * existing Student lookup (StudentController) is client-scoped --
 * /clients/{clientId}/students/{id} -- but Employee Search is
 * deliberately global and cross-client, and real DIBs data shows a
 * genuine, non-rare case of a student with NO employer client at all
 * ("No Client Record" -- confirmed with Michael this is a legacy-data
 * pattern expected from the future client-database migration, not
 * something that happens organically going forward, since every
 * student created through our own system always gets a real
 * employerClient, even a purely Individual one).
 *
 * Built proactively now, not deferred -- confirmed with Michael:
 * without this, an Employee Search result for a no-client-record
 * student would be a dead end in the UI the moment that legacy data
 * exists, with nowhere to link to at all.
 *
 * A separate, small controller rather than added to StudentController
 * -- that one's class-level @RequestMapping is client-scoped, and this
 * genuinely different, global lookup doesn't fit inside it cleanly.
 */
@RestController
public class GlobalStudentController {

    private final StudentRepository studentRepository;

    public GlobalStudentController(StudentRepository studentRepository) {
        this.studentRepository = studentRepository;
    }

    @GetMapping("/api/v1/students/{studentId}")
    @org.springframework.transaction.annotation.Transactional(readOnly = true)
    public ResponseEntity<Student> get(@PathVariable Long studentId) {
        return studentRepository.findById(studentId)
                .map(student -> {
                    // Michael, 2026-08-30 -- same reasoning as
                    // LectureCertificateService's own fix: @Transactional
                    // alone keeps the session open, but Spring Boot's
                    // default Hibernate/Jackson integration doesn't force
                    // lazy proxies to load during serialization on its
                    // own -- forced explicitly here, while still inside
                    // the transaction.
                    org.hibernate.Hibernate.initialize(student.getEmployerClient());
                    return ResponseEntity.ok(student);
                })
                .orElseGet(() -> ResponseEntity.notFound().build());
    }
}
