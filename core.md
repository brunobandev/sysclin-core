### Core — SysClin

Documentação das entidades implementadas no sistema. Todos os nomes de modelos, tabelas e colunas usam inglês.

---

## User (Usuário)

- **Model:** `App\Models\User`
- **Table:** `users`
- **Enum:** `App\Enums\UserRole` (Doctor, Secretary, Technician)
- **Auth:** Laravel Fortify (login, registro, 2FA)

| Column | Type | Notes |
|--------|------|-------|
| id | integer | PK |
| name | varchar | |
| email | varchar | unique |
| email_verified_at | datetime | nullable |
| password | varchar | |
| role | varchar | UserRole enum (doctor, secretary, technician) |
| crm_coren | varchar | nullable, não se aplica a secretária |
| specialty | varchar | nullable |
| remember_token | varchar | nullable |
| two_factor_secret | text | nullable, Fortify 2FA |
| two_factor_recovery_codes | text | nullable |
| two_factor_confirmed_at | datetime | nullable |

**Relationships:** `medicalRecords(): HasMany`, `prescriptions(): HasMany`
**Helper methods:** `isDoctor()`, `isSecretary()`, `isTechnician()`

---

## Patient (Paciente)

- **Model:** `App\Models\Patient`
- **Table:** `patients`

| Column | Type | Notes |
|--------|------|-------|
| id | integer | PK |
| name | varchar | required |
| dob | date | data de nascimento, required |
| gender | varchar | M, F, NA |
| phone | varchar | required |
| avatar | varchar | nullable, foto |

**Relationships:** `medicalRecords(): HasMany`, `prescriptions(): HasMany`, `chronicMedication(): HasOne`

---

## HealthInsurance (Convênio)

- **Model:** `App\Models\HealthInsurance`
- **Table:** `health_insurances`

| Column | Type | Notes |
|--------|------|-------|
| id | integer | PK |
| name | varchar | required |

---

## Facility (Sede)

- **Model:** `App\Models\Facility`
- **Table:** `facilities`

| Column | Type | Notes |
|--------|------|-------|
| id | integer | PK |
| name | varchar | required |
| address | varchar | nullable |
| phone | varchar | nullable |

---

## Room (Sala)

- **Model:** `App\Models\Room`
- **Table:** `rooms`

| Column | Type | Notes |
|--------|------|-------|
| id | integer | PK |
| facility_id | integer | FK → facilities.id |
| name | varchar | required |

**Relationships:** `facility(): BelongsTo`

---

## AppointmentType (Tipo de Agendamento)

- **Model:** `App\Models\AppointmentType`
- **Table:** `appointment_types`

| Column | Type | Notes |
|--------|------|-------|
| id | integer | PK |
| name | varchar | ex: Consulta, Exame |
| color | varchar | cor para exibição na agenda |

---

## AppointmentStatus (Status do Agendamento)

- **Model:** `App\Models\AppointmentStatus`
- **Table:** `appointment_statuses`

| Column | Type | Notes |
|--------|------|-------|
| id | integer | PK |
| name | varchar | ex: Agendado, Confirmado, Cancelado |

---

## Appointment (Agendamento)

- **Model:** `App\Models\Appointment`
- **Table:** `appointments`

| Column | Type | Notes |
|--------|------|-------|
| id | integer | PK |
| patient_id | integer | FK → patients.id, cascade |
| user_id | integer | FK → users.id (médico), cascade |
| room_id | integer | FK → rooms.id, cascade |
| appointment_type_id | integer | FK → appointment_types.id, cascade |
| appointment_status_id | integer | FK → appointment_statuses.id, cascade |
| health_insurance_id | integer | FK → health_insurances.id, cascade |
| start_at | datetime | início |
| end_at | datetime | fim |
| notes | text | nullable, motivo/observações |

**Relationships:** `patient(): BelongsTo`, `user(): BelongsTo`, `room(): BelongsTo`, `appointmentType(): BelongsTo`, `appointmentStatus(): BelongsTo`, `healthInsurance(): BelongsTo`

---

## MedicalRecord (Prontuário)

- **Model:** `App\Models\MedicalRecord`
- **Table:** `medical_records`
- **Gate:** `manage-medical-records` (somente médicos)

| Column | Type | Notes |
|--------|------|-------|
| id | integer | PK |
| patient_id | integer | FK → patients.id, cascade |
| user_id | integer | FK → users.id (médico), cascade |
| reason | varchar | nullable, motivo |
| disease_cid | varchar | nullable, CID da doença |
| subjective | text | nullable, SOAP - subjetivo |
| objective | text | nullable, SOAP - objetivo |
| exams | text | nullable, exames |
| impression | text | nullable, impressão |
| conduct | text | nullable, conduta |
| description | text | nullable, descrição geral |

**Relationships:** `patient(): BelongsTo`, `user(): BelongsTo`, `photos(): HasMany`

---

## MedicalRecordPhoto (Foto do Prontuário)

- **Model:** `App\Models\MedicalRecordPhoto`
- **Table:** `medical_record_photos`

| Column | Type | Notes |
|--------|------|-------|
| id | integer | PK |
| medical_record_id | integer | FK → medical_records.id, cascade |
| path | varchar | caminho no disco (private) |
| original_name | varchar | nome original do arquivo |

**Relationships:** `medicalRecord(): BelongsTo`

---

## Prescription (Receituário)

- **Model:** `App\Models\Prescription`
- **Table:** `prescriptions`
- **Enum:** `App\Enums\PrescriptionType` (Simples, ControleEspecial)
- **Gate:** `manage-prescriptions` (somente médicos)

| Column | Type | Notes |
|--------|------|-------|
| id | integer | PK |
| patient_id | integer | FK → patients.id, cascade |
| user_id | integer | FK → users.id (médico), cascade |
| type | varchar | PrescriptionType enum |
| usage_type | varchar | nullable, tipo de uso |
| disease_cid | varchar | nullable |
| notes | text | nullable, observações |

**Relationships:** `patient(): BelongsTo`, `user(): BelongsTo`, `items(): HasMany`

---

## PrescriptionItem (Item do Receituário)

- **Model:** `App\Models\PrescriptionItem`
- **Table:** `prescription_items`

| Column | Type | Notes |
|--------|------|-------|
| id | integer | PK |
| prescription_id | integer | FK → prescriptions.id, cascade |
| medication | varchar | medicamento |
| quantity | varchar | quantidade |
| frequency | varchar | frequência |
| usage_type | varchar | nullable, tipo de uso |

**Relationships:** `prescription(): BelongsTo`

---

## PrescriptionTemplate (Modelo de Receituário)

- **Model:** `App\Models\PrescriptionTemplate`
- **Table:** `prescription_templates`
- **Gate:** `manage-prescription-templates` (somente médicos)

| Column | Type | Notes |
|--------|------|-------|
| id | integer | PK |
| user_id | integer | FK → users.id (médico), cascade |
| name | varchar | nome do modelo |

**Relationships:** `user(): BelongsTo`, `items(): HasMany`
**Note:** Templates são por médico. Na página de receituários, o botão "Carregar modelo" lista os templates do médico logado e popula os itens.

---

## PrescriptionTemplateItem (Item do Modelo de Receituário)

- **Model:** `App\Models\PrescriptionTemplateItem`
- **Table:** `prescription_template_items`

| Column | Type | Notes |
|--------|------|-------|
| id | integer | PK |
| prescription_template_id | integer | FK → prescription_templates.id, cascade |
| medication | varchar | medicamento |
| quantity | varchar | quantidade |
| frequency | varchar | frequência |
| usage_type | varchar | nullable, tipo de uso |

**Relationships:** `prescriptionTemplate(): BelongsTo`

---

## CertificateTemplate (Modelo de Atestado)

- **Model:** `App\Models\CertificateTemplate`
- **Table:** `certificate_templates`
- **Gate:** `manage-certificate-templates` (somente médicos)

| Column | Type | Notes |
|--------|------|-------|
| id | integer | PK |
| name | varchar | nome do modelo |
| content | text | conteúdo do atestado |
| cid | varchar | nullable, CID para atestados |

**Relationships:** nenhuma

---

## ChronicMedication (Medicação Crônica)

- **Model:** `App\Models\ChronicMedication`
- **Table:** `chronic_medications`
- **Gate:** nenhum (acessível a todos os usuários autenticados)

| Column | Type | Notes |
|--------|------|-------|
| id | integer | PK |
| patient_id | integer | FK → patients.id, cascade, **unique** |
| medications | text | lista de medicações em texto livre |

**Relationships:** `patient(): BelongsTo`
**Note:** Um paciente tem no máximo uma entrada. Usa `updateOrCreate` para salvar.

---

## Rotas

| URL | Livewire Component | Route Name | Middleware |
|-----|--------------------|------------|------------|
| `/` | — | `home` | — |
| `/dashboard` | — | `dashboard` | `auth`, `verified` |
| `/agenda` | `pages::appointment.list` | `appointment.list` | auth (Folio) |
| `/pacientes` | `pages::patient.list` | `patient.list` | auth (Folio) |
| `/planos-de-saude` | `pages::health-insurance.list` | `health-insurance.list` | auth (Folio) |
| `/sedes` | `pages::facility.list` | `facility.list` | auth (Folio) |
| `/salas` | `pages::room.list` | `room.list` | auth (Folio) |
| `/prontuarios` | `pages::medical-record.list` | `medical-record.list` | `can:manage-medical-records` |
| `/receituarios` | `pages::prescription.list` | `prescription.list` | `can:manage-prescriptions` |
| `/modelos-receituario` | `pages::prescription-template.list` | `prescription-template.list` | `can:manage-prescription-templates` |
| `/modelos-atestado` | `pages::certificate-template.list` | `certificate-template.list` | `can:manage-certificate-templates` |
| `/medicacoes-cronicas` | `pages::chronic-medication.list` | `chronic-medication.list` | auth (Folio) |

---

## Sidebar (Navegação)

- **Platform:** Dashboard, Agenda, Pacientes, Medicações Crônicas
- **Clínico** (`@can('manage-medical-records')`): Prontuários, Receituários
- **Modelos** (`@can('manage-certificate-templates')`): Modelos de Receituário, Modelos de Atestado
- **Cadastros:** Planos de saúde, Salas, Sedes

---

## Authorization Gates

| Gate | Allowed Roles |
|------|---------------|
| `manage-medical-records` | Doctor |
| `manage-prescriptions` | Doctor |
| `manage-prescription-templates` | Doctor |
| `manage-certificate-templates` | Doctor |

Definidos em `app/Providers/AppServiceProvider.php`.

---

## Testes

**99 tests, 224 assertions** — todos passando.

| Test File | Count |
|-----------|-------|
| `tests/Feature/MedicalRecordTest.php` | 11 |
| `tests/Feature/PrescriptionTest.php` | 11 |
| `tests/Feature/PrescriptionTemplateTest.php` | 9 |
| `tests/Feature/CertificateTemplateTest.php` | 8 |
| `tests/Feature/ChronicMedicationTest.php` | 7 |
| Other existing tests | 53 |
