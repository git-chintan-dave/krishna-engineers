# Krishna Engineers — V1 Database

## Tables

1. `users`
2. `machines`
3. `sections`
4. `section_sizes`
5. `parts`
6. `setups`
7. `part_setups`
8. `tools`
9. `attendances`
10. `attendance_breaks`
11. `operator_work_logs`
12. `bandsaw_work_logs`
13. `raw_material_transactions`
14. `tool_transactions`
15. `salaries`

## 1. users

| Column            | Type        | Notes                                                                     |
| ----------------- | ----------- | ------------------------------------------------------------------------- |
| id                | bigint      | PK                                                                        |
| reference_id      | string      | Unique employee/punch-machine ID                                          |
| first_name        | string      | Required                                                                  |
| last_name         | string      | Nullable                                                                  |
| mobile            | string      | Nullable                                                                  |
| email             | string      | Nullable                                                                  |
| password          | string      | Hashed                                                                    |
| role              | enum        | `super_admin`, `supervisor`, `operator`, `bandsaw`, `deburring_packaging` |
| shift             | string/enum | Actual shift values to be finalized later                                 |
| salary            | decimal     | Monthly salary                                                            |
| incentive_per_day | decimal     | Employee-specific incentive rate                                          |
| deduction_per_day | decimal     | Employee-specific deduction rate                                          |
| status            | enum        | `active`, `inactive`                                                      |
| created_at        | datetime    |                                                                           |
| updated_at        | datetime    |                                                                           |

## 2. machines

| Column     | Type     | Notes                |
| ---------- | -------- | -------------------- |
| id         | bigint   | PK                   |
| name       | string   | Machine name         |
| short_name | string   | Short name           |
| status     | enum     | `active`, `inactive` |
| created_at | datetime |                      |
| updated_at | datetime |                      |

## 3. sections

| Column      | Type     | Notes                     |
| ----------- | -------- | ------------------------- |
| id          | bigint   | PK                        |
| name        | string   | Section/raw-material name |
| description | text     | Nullable                  |
| status      | enum     | `active`, `inactive`      |
| created_at  | datetime |                           |
| updated_at  | datetime |                           |

## 4. section_sizes

| Column         | Type     | Notes                   |
| -------------- | -------- | ----------------------- |
| id             | bigint   | PK                      |
| section_id     | bigint   | FK → sections.id        |
| size           | string   | Example `127*25`        |
| stock_quantity | decimal  | Current available stock |
| status         | enum     | `active`, `inactive`    |
| created_at     | datetime |                         |
| updated_at     | datetime |                         |

Constraint: `unique(section_id, size)`

Stock is maintained through `raw_material_transactions`.

## 5. parts

| Column         | Type     | Notes                    |
| -------------- | -------- | ------------------------ |
| id             | bigint   | PK                       |
| name           | string   | Full/original part name  |
| reference_name | string   | Short/easy internal name |
| image          | string   | Nullable                 |
| status         | enum     | `active`, `inactive`     |
| created_at     | datetime |                          |
| updated_at     | datetime |                          |

No direct machine relationship.

## 6. setups

Setup is a reusable master containing only names such as `S1`, `S2`.

| Column     | Type     | Notes              |
| ---------- | -------- | ------------------ |
| id         | bigint   | PK                 |
| name       | string   | Example `S1`, `S2` |
| created_at | datetime |                    |
| updated_at | datetime |                    |

Constraint: `unique(name)`

No image, machine, or cycle-time fields.

## 7. part_setups

| Column             | Type     | Notes          |
| ------------------ | -------- | -------------- |
| id                 | bigint   | PK             |
| part_id            | bigint   | FK → parts.id  |
| setup_id           | bigint   | FK → setups.id |
| image              | string   | Nullable       |
| machine_cycle_time | integer  | Seconds        |
| actual_cycle_time  | integer  | Seconds        |
| created_at         | datetime |                |
| updated_at         | datetime |                |

Constraint: `unique(part_id, setup_id)`

### Cycle-time terminology

**Machine Cycle Time:** time taken by the machine to complete one cycle.

**Actual Cycle Time:** complete practical time between two production cycles, including loading/unloading, machine operation, checking/inspection, cleaning, and normal operator actions.

Inspection is included in Actual Cycle Time.

Employee may edit Actual Cycle Time for an individual work log when the actual cycle differs, including tool/setup changes. No separate change tracking is required.

## 8. tools

| Column         | Type     | Notes                                         |
| -------------- | -------- | --------------------------------------------- |
| id             | bigint   | PK                                            |
| name           | string   | Tool name                                     |
| type           | enum     | `endmill`, `drill`, `insert`, `special_tools` |
| image          | string   | Nullable                                      |
| total_quantity | decimal  | Total physical quantity                       |
| status         | enum     | `active`, `inactive`                          |
| created_at     | datetime |                                               |
| updated_at     | datetime |                                               |

Tool flow:

- Super Admin → Supervisor
- Supervisor → Operator
- Super Admin → Operator

Supervisor can return tools to Super Admin.

Example: total 10, issue 5 to Supervisor, then Supervisor issues 2 to Operator:

- Super Admin available = 5
- Supervisor custody = 3
- Operator custody = 2

Tool issue changes custody, not automatic consumption.

## 9. attendances

| Column                    | Type     | Notes                   |
| ------------------------- | -------- | ----------------------- |
| id                        | bigint   | PK                      |
| user_id                   | bigint   | FK → users.id           |
| attendance_date           | date     | Date of IN/start        |
| start_at                  | datetime | Actual IN datetime      |
| end_at                    | datetime | Actual OUT datetime     |
| total_working_seconds     | integer  | `end_at - start_at`     |
| break_seconds             | integer  | Actual/default break    |
| effective_working_seconds | integer  | Total minus breaks      |
| source                    | string   | Example `api`, `manual` |
| is_manual                 | boolean  | Manual correction flag  |
| created_at                | datetime |                         |
| updated_at                | datetime |                         |

Attendance date uses the IN date.

Example:
`23 Aug 20:00 → 24 Aug 07:00` has `attendance_date = 23 Aug`.

Default break = 3600 seconds.

If actual break punches exist, use their actual duration instead of the default.

## 10. attendance_breaks

| Column           | Type     | Notes               |
| ---------------- | -------- | ------------------- |
| id               | bigint   | PK                  |
| attendance_id    | bigint   | FK → attendances.id |
| start_at         | datetime | Break start         |
| end_at           | datetime | Break end           |
| duration_seconds | integer  | Calculated duration |
| created_at       | datetime |                     |
| updated_at       | datetime |                     |

If no API break punches exist, no break rows are required and the attendance uses the 1-hour default.

## 11. operator_work_logs

| Column             | Type        | Notes                                 |
| ------------------ | ----------- | ------------------------------------- |
| id                 | bigint      | PK                                    |
| user_id            | bigint      | FK → users.id                         |
| work_date          | date        | Work date                             |
| shift              | string/enum | Shift during work                     |
| machine_id         | bigint      | FK → machines.id                      |
| part_id            | bigint      | FK → parts.id                         |
| setup_id           | bigint      | FK → setups.id                        |
| part_setup_id      | bigint      | FK → part_setups.id                   |
| machine_cycle_time | integer     | Snapshot in seconds                   |
| actual_cycle_time  | integer     | Snapshot/employee override in seconds |
| quantity           | decimal     | Produced quantity                     |
| rejection_quantity | decimal     | Rejected quantity                     |
| production_seconds | integer     | Calculated                            |
| remarks            | text        | Nullable                              |
| created_at         | datetime    |                                       |
| updated_at         | datetime    |                                       |

When Part + Setup is selected, load its image and cycle times from `part_setups`.

Production:
`production_seconds = quantity × actual_cycle_time`

Employee can edit actual cycle time for that work log only.

After employee submission:

- Employee cannot edit/delete.
- Supervisor/Super Admin can edit/delete.

If production time is greater than attendance working time, save actual data as entered. Super Admin can review/update it if noticed.

## 12. bandsaw_work_logs

| Column          | Type     | Notes                 |
| --------------- | -------- | --------------------- |
| id              | bigint   | PK                    |
| user_id         | bigint   | FK → users.id         |
| work_date       | date     | Work date             |
| part_id         | bigint   | FK → parts.id         |
| section_size_id | bigint   | FK → section_sizes.id |
| quantity        | decimal  | Quantity consumed/cut |
| remarks         | text     | Nullable              |
| created_at      | datetime |                       |
| updated_at      | datetime |                       |

On submission:

1. Validate available stock.
2. Create `outward` raw-material transaction.
3. Reduce `section_sizes.stock_quantity`.
4. Save work log.

No leftover/remnant tracking; leftover handling is manual.

## 13. raw_material_transactions

| Column          | Type     | Notes                                          |
| --------------- | -------- | ---------------------------------------------- |
| id              | bigint   | PK                                             |
| section_size_id | bigint   | FK → section_sizes.id                          |
| type            | enum     | `opening`, `purchase`, `outward`, `adjustment` |
| quantity        | decimal  | Transaction quantity                           |
| user_id         | bigint   | Nullable                                       |
| reference_type  | string   | Nullable                                       |
| reference_id    | bigint   | Nullable                                       |
| remarks         | text     | Nullable                                       |
| created_at      | datetime |                                                |
| updated_at      | datetime |                                                |

Stock:
`opening + purchase - outward +/- adjustment`

`section_sizes.stock_quantity` stores current balance; transactions store history.

## 14. tool_transactions

| Column             | Type     | Notes                                                  |
| ------------------ | -------- | ------------------------------------------------------ |
| id                 | bigint   | PK                                                     |
| tool_id            | bigint   | FK → tools.id                                          |
| type               | enum     | `opening`, `purchase`, `issue`, `return`, `adjustment` |
| quantity           | decimal  | Quantity                                               |
| from_user_id       | bigint   | Nullable                                               |
| to_user_id         | bigint   | Nullable                                               |
| part_id            | bigint   | Nullable                                               |
| setup_id           | bigint   | Nullable                                               |
| return_scrap       | boolean  | Nullable                                               |
| created_by_user_id | bigint   | FK → users.id                                          |
| remarks            | text     | Nullable                                               |
| created_at         | datetime |                                                        |
| updated_at         | datetime |                                                        |

Only Super Admin/Supervisor create normal issue/return entries.

`return_scrap` is a simple yes/no confirmation. No tool consumption or partial-return tracking.

## 15. salaries

| Column              | Type     | Notes                 |
| ------------------- | -------- | --------------------- |
| id                  | bigint   | PK                    |
| user_id             | bigint   | FK → users.id         |
| salary_period_start | date     | First day of month    |
| salary_period_end   | date     | Last day of month     |
| base_salary         | decimal  | Salary                |
| working_days        | integer  | Total attendance days |
| upad                | decimal  | Salary advance        |
| paid_loan           | decimal  | Loan installment      |
| incentive_rate      | decimal  | Per-day rate          |
| incentive           | decimal  | Calculated            |
| deduction_rate      | decimal  | Per-day rate          |
| deduction           | decimal  | Calculated            |
| adjustment          | decimal  | Positive/negative     |
| adjustment_reason   | text     | Nullable              |
| final_payable       | decimal  | Final salary          |
| status              | enum     | `draft`, `paid`       |
| paid_at             | datetime | Nullable              |
| created_at          | datetime |                       |
| updated_at          | datetime |                       |

Working days = total attendance days in the salary period.

Incentive:
`eligible_days = working_days - 23`
`incentive = eligible_days × incentive_rate`

Deduction:
`deduction = (21 - working_days) × deduction_rate`

Example for 19 days:
`deduction = (21 - 19) × deduction_rate`

Final:
`final_payable = base_salary - upad - paid_loan + incentive - deduction + adjustment`

Once paid, salary is locked.

---

# Efficiency

```text
productive_time = SUM(operator_work_logs.production_seconds)

effective_working_time = attendance.effective_working_seconds

efficiency = productive_time / effective_working_time × 100
```

Efficiency is not capped at 100%.

Rejection quantity does not reduce efficiency.

---

# V1 Excluded

- Machine maintenance
- Machine downtime tracking
- Waiting-for-material tracking
- Tool consumption tracking
- Tool-to-Part master assignment
- Tool-to-Setup master assignment
- Leftover/remnant tracking
- Finished-goods inventory
- Rejection reason master
- Attendance correction history/audit
- Loan management
- Salary finalized status
- Work-log approval/rejection workflow
- Separate tool-change tracking
- Separate setup-change tracking
- Separate inspection workflow
- Paper-form printing
- Historical paper-data import
