package com.caa.platform.vr;

import com.caa.platform.staff.StaffUser;
import com.caa.platform.student.Student;
import jakarta.persistence.*;
import lombok.Getter;
import lombok.NoArgsConstructor;
import lombok.Setter;
import org.hibernate.annotations.JdbcTypeCode;
import org.hibernate.type.SqlTypes;

import java.time.LocalDate;
import java.time.OffsetDateTime;

/**
 * Section 4b: escalation rules (e.g. "after N attempts, call") live in
 * staff SOPs, not hardcoded system logic. This just records what outreach
 * actually happened, so the SOP can evolve without a code change.
 */
@Entity
@Table(name = "outreach_logs")
@Getter
@Setter
@NoArgsConstructor
public class OutreachLog {

    @Id
    @GeneratedValue(strategy = GenerationType.IDENTITY)
    private Long id;

    @ManyToOne(fetch = FetchType.LAZY, optional = false)
    @JoinColumn(name = "student_id", nullable = false)
    private Student student;

    @Column(name = "outreach_date", nullable = false)
    private LocalDate outreachDate = LocalDate.now();

    @Enumerated(EnumType.STRING)
    @JdbcTypeCode(SqlTypes.NAMED_ENUM)
    @Column(nullable = false)
    private OutreachMethod method;

    @ManyToOne(fetch = FetchType.LAZY, optional = false)
    @JoinColumn(name = "staff_member_id", nullable = false)
    private StaffUser staffMember;

    private String notes;

    @Column(name = "created_at", nullable = false, updatable = false)
    private OffsetDateTime createdAt = OffsetDateTime.now();
}
