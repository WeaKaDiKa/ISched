<?php
require_once __DIR__ . '/../config/db_connection.php';

class Appointment {
    private $conn;
    private $table = 'appointments';

    public function __construct() {
        $database = new Database();
        $this->conn = $database->connect();
    }

    public function getApprovedAppointments() {
        try {
            $query = "SELECT a.*, 
                            p.name as patient_name, 
                            p.profile_photo,
                            s.service_name,
                            d.name as doctor_name,
                            ts.start_time,
                            ts.end_time
                     FROM " . $this->table . " a
                     LEFT JOIN patients p ON a.patient_id = p.id
                     LEFT JOIN services s ON a.service_id = s.id
                     LEFT JOIN doctors d ON a.doctor_id = d.id
                     LEFT JOIN time_slots ts ON a.time_slot = ts.id
                     WHERE a.status = 'approved'
                     ORDER BY a.appointment_date ASC, ts.start_time ASC";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            return [];
        }
    }

    public function getUpcomingAppointments() {
        try {
            $today = date('Y-m-d');
            $query = "SELECT a.*, 
                            p.name as patient_name, 
                            p.profile_photo,
                            s.service_name,
                            d.name as doctor_name,
                            ts.start_time,
                            ts.end_time
                     FROM " . $this->table . " a
                     LEFT JOIN patients p ON a.patient_id = p.id
                     LEFT JOIN services s ON a.service_id = s.id
                     LEFT JOIN doctors d ON a.doctor_id = d.id
                     LEFT JOIN time_slots ts ON a.time_slot = ts.id
                     WHERE a.status = 'approved' 
                     AND a.appointment_date >= :today
                     ORDER BY a.appointment_date ASC, ts.start_time ASC";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':today', $today);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            return [];
        }
    }

    public function getPatientAppointments($patientId) {
        try {
            $query = "SELECT a.*, 
                            s.service_name,
                            d.name as doctor_name,
                            ts.start_time,
                            ts.end_time,
                            a.appointment_date,
                            a.status
                     FROM " . $this->table . " a
                     LEFT JOIN services s ON a.service_id = s.id
                     LEFT JOIN doctors d ON a.doctor_id = d.id
                     LEFT JOIN time_slots ts ON a.time_slot = ts.id
                     WHERE a.patient_id = :patient_id
                     AND a.status = 'approved'
                     ORDER BY a.appointment_date ASC, ts.start_time ASC";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':patient_id', $patientId);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            return [];
        }
    }

    public function updateAppointmentStatus($appointmentId, $status) {
        try {
            $query = "UPDATE " . $this->table . " 
                     SET status = :status 
                     WHERE id = :id";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':status', $status);
            $stmt->bindParam(':id', $appointmentId);
            return $stmt->execute();
        } catch(PDOException $e) {
            return false;
        }
    }
}
?> 