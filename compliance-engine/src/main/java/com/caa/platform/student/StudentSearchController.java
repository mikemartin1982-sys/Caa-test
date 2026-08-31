package com.caa.platform.student;

import com.caa.platform.client.Client;
import org.springframework.http.ResponseEntity;
import org.springframework.transaction.annotation.Transactional;
import org.springframework.web.bind.annotation.GetMapping;
import org.springframework.web.bind.annotation.RequestMapping;
import org.springframework.web.bind.annotation.RequestParam;
import org.springframework.web.bind.annotation.RestController;

import java.util.List;

/**
 * Michael, 2026-08-24 -- "Find Employee": a global, cross-client
 * search. Every existing student endpoint (StudentController) is
 * scoped under a specific client's ID -- you have to already know who
 * someone works for before you can look them up. This is the
 * counterpart: search across every student regardless of employer,
 * matching the exact pattern already proven on Client search
 * (ClientController.search()) -- purely numeric queries match student
 * ID only (not the "S"-prefixed studentNumber, confirmed with Michael:
 * the raw, scaling database ID is what should match exactly, from 1
 * upward), everything else matches name/email/phone/studentNumber.
 *
 * A separate controller rather than added to StudentController --
 * that one is deliberately scoped to /clients/{clientId}/students
 * throughout; mixing in a top-level /students path there would mean
 * two different URL scopes on one class.
 */
@RestController
@RequestMapping("/api/v1/students")
public class StudentSearchController {

    private final StudentRepository studentRepository;

    public StudentSearchController(StudentRepository studentRepository) {
        this.studentRepository = studentRepository;
    }

    /**
     * A result includes the employer client's own id/name -- a global
     * search result on its own isn't actionable otherwise; staff need
     * to know which client's Employees page to actually go to.
     */
    public record StudentSearchResult(Long id, String studentNumber, String name, String email, String phone,
                                       boolean active, Long employerClientId, String employerClientName) {}

    @GetMapping
    @Transactional(readOnly = true)
    public ResponseEntity<List<StudentSearchResult>> search(@RequestParam(required = false) String q) {
        List<Student> matches;

        if (q == null || q.isBlank()) {
            matches = studentRepository.findAll();
        } else if (q.chars().allMatch(Character::isDigit)) {
            // Purely numeric -- ID only, same reasoning and same fix
            // as ClientController.search(): a numeric query means
            // someone's looking up a specific student by their exact,
            // scaling ID (1, 2, 3... upward), not a text/name search
            // that happens to contain that digit somewhere.
            String idQuery = q;
            matches = studentRepository.findAll().stream()
                    .filter(s -> s.getId() != null && s.getId().toString().startsWith(idQuery))
                    .toList();
        } else {
            String needle = q.toLowerCase();
            matches = studentRepository.findAll().stream()
                    .filter(s -> containsIgnoreCase(s.getName(), needle)
                            || containsIgnoreCase(s.getEmail(), needle)
                            || containsIgnoreCase(s.getPhone(), needle)
                            || containsIgnoreCase(s.getStudentNumber(), needle))
                    .toList();
        }

        List<StudentSearchResult> results = matches.stream()
                .map(s -> {
                    Client employer = s.getEmployerClient();
                    String employerName = employer != null
                            ? (employer.getRecordName() != null ? employer.getRecordName() : employer.getCompany())
                            : null;
                    return new StudentSearchResult(
                            s.getId(), s.getStudentNumber(), s.getName(), s.getEmail(), s.getPhone(), s.isActive(),
                            employer != null ? employer.getId() : null, employerName
                    );
                })
                .toList();

        return ResponseEntity.ok(results);
    }

    private boolean containsIgnoreCase(String haystack, String needleLower) {
        return haystack != null && haystack.toLowerCase().contains(needleLower);
    }
}
