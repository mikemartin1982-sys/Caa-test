package com.caa.platform.session;

import com.caa.platform.staff.StaffUser;
import jakarta.persistence.*;
import lombok.Getter;
import lombok.NoArgsConstructor;
import lombok.Setter;
import org.hibernate.annotations.JdbcTypeCode;
import org.hibernate.type.SqlTypes;

import java.time.OffsetDateTime;

/**
 * Section 4c: Confirmed checkbox + required comment, and Team Comments.
 * WRITE-ONCE / IMMUTABLE -- no edit or delete, ever, enforced by never
 * exposing an update/delete endpoint for this entity (see db/README.md
 * for the corresponding REVOKE UPDATE/DELETE recommendation at the DB role
 * level). Displayed Central Time as M/D/YYYY h:mm AM/PM; stored UTC here.
 * Scoped strictly to the individual Session instance -- does not carry
 * forward on "Copy Forward 6 Months" (Section 4c).
 */
@Entity
@Table(name = "session_comments")
@Getter
@Setter
@NoArgsConstructor
public class SessionComment {

    @Id
    @GeneratedValue(strategy = GenerationType.IDENTITY)
    private Long id;

    @ManyToOne(fetch = FetchType.LAZY, optional = false)
    @JoinColumn(name = "session_id", nullable = false)
    private Session session;

    @Enumerated(EnumType.STRING)
    @JdbcTypeCode(SqlTypes.NAMED_ENUM)
    @Column(name = "comment_type", nullable = false)
    private SessionCommentType commentType;

    @Column(nullable = false)
    private String text;

    @ManyToOne(fetch = FetchType.LAZY, optional = false)
    @JoinColumn(name = "author_id", nullable = false)
    private StaffUser author;

    @Column(name = "created_at_utc", nullable = false)
    private OffsetDateTime createdAtUtc = OffsetDateTime.now();
}
