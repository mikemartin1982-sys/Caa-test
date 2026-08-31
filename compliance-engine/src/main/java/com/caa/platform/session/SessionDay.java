package com.caa.platform.session;

import jakarta.persistence.*;
import lombok.Getter;
import lombok.NoArgsConstructor;
import lombok.Setter;

import java.time.LocalDate;
import java.time.LocalTime;

/**
 * Section 3: multi-day session support. Private/Semi-Private start time is
 * negotiated with the client; Public defaults ~8am-5pm. Client-level and
 * session-level data carries across all days of the same Session.
 */
@Entity
@Table(name = "session_days")
@Getter
@Setter
@NoArgsConstructor
public class SessionDay {

    @Id
    @GeneratedValue(strategy = GenerationType.IDENTITY)
    private Long id;

    @ManyToOne(fetch = FetchType.LAZY, optional = false)
    @JoinColumn(name = "session_id", nullable = false)
    private Session session;

    @Column(name = "day_number", nullable = false)
    private Integer dayNumber;

    @Column(name = "session_date", nullable = false)
    private LocalDate sessionDate;

    @Column(name = "start_time", nullable = false)
    private LocalTime startTime;

    @Column(name = "end_time", nullable = false)
    private LocalTime endTime;
}
