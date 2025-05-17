<?php
require_once __DIR__ . '/../config/db_connection.php';

class Patient {
    private $conn;
    private $table = 'patients';

    public function __construct() {
        $database = new Database();
        $this->conn = $database->connect();
    }

    public function getAllPatients() {
        try {
            $query = "SELECT p.*, pp.* 
                     FROM " . $this->table . " p 
                     LEFT JOIN patient_profiles pp ON p.id = pp.patient_id
                     LEFT JOIN appointments a ON p.id = a.patient_id AND a.status = 'completed'
                     GROUP BY p.id
                     ORDER BY p.name ASC";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            return [];
        }
    }

    public function getPatientDetails($patientId) {
        try {
            $query = "SELECT p.*, pp.* 
                     FROM " . $this->table . " p 
                     LEFT JOIN patient_profiles pp ON p.id = pp.patient_id
                     WHERE p.id = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $patientId);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            return null;
        }
    }

    public function getPatientAppointments($patientId) {
        try {
            $query = "SELECT a.*, s.service_name, d.name as doctor_name
                     FROM appointments a 
                     LEFT JOIN services s ON a.service_id = s.id
                     LEFT JOIN doctors d ON a.doctor_id = d.id
                     WHERE a.patient_id = :patient_id 
                     ORDER BY a.appointment_date DESC, a.time_slot DESC";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':patient_id', $patientId);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            return [];
        }
    }

    public function getPatientTreatments($patientId) {
        try {
            $query = "SELECT t.*, s.service_name, d.name as doctor_name
                     FROM treatments t
                     LEFT JOIN services s ON t.service_id = s.id
                     LEFT JOIN doctors d ON t.doctor_id = d.id
                     WHERE t.patient_id = :patient_id 
                     ORDER BY t.treatment_date DESC, t.treatment_time DESC";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':patient_id', $patientId);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            return [];
        }
    }

    public function getMedicalHistory($patientId) {
        try {
            $query = "SELECT * FROM medical_history 
                     WHERE patient_id = :patient_id 
                     ORDER BY updated_at DESC 
                     LIMIT 1";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':patient_id', $patientId);
            $stmt->execute();
            $medicalHistory = $stmt->fetch(PDO::FETCH_ASSOC);

            // If no medical history exists yet, return empty structure
            if (!$medicalHistory) {
                return [
                    'allergies' => '',
                    'blood_type' => '',
                    'blood_pressure' => '',
                    'heart_disease' => 'No',
                    'diabetes' => 'No',
                    'current_medications' => '',
                    'medical_conditions' => '',
                    'last_physical_exam' => null
                ];
            }

            return $medicalHistory;
        } catch(PDOException $e) {
            return null;
        }
    }

    // Add new method to save medical history
    public function saveMedicalHistory($patientId, $data) {
        try {
            $query = "INSERT INTO medical_history 
                     (patient_id, allergies, blood_type, blood_pressure, heart_disease, 
                      diabetes, current_medications, medical_conditions, last_physical_exam) 
                     VALUES 
                     (:patient_id, :allergies, :blood_type, :blood_pressure, :heart_disease,
                      :diabetes, :current_medications, :medical_conditions, :last_physical_exam)";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':patient_id', $patientId);
            $stmt->bindParam(':allergies', $data['allergies']);
            $stmt->bindParam(':blood_type', $data['blood_type']);
            $stmt->bindParam(':blood_pressure', $data['blood_pressure']);
            $stmt->bindParam(':heart_disease', $data['heart_disease']);
            $stmt->bindParam(':diabetes', $data['diabetes']);
            $stmt->bindParam(':current_medications', $data['current_medications']);
            $stmt->bindParam(':medical_conditions', $data['medical_conditions']);
            $stmt->bindParam(':last_physical_exam', $data['last_physical_exam']);
            
            return $stmt->execute();
        } catch(PDOException $e) {
            return false;
        }
    }
}
?> 