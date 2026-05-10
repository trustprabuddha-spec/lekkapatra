import { useEffect, useMemo, useState, type FormEvent } from 'react';
import { createEmiPlan, createFeeType, fetchCurrentUser, fetchFeeTypes, fetchStudents, generateBill, invoicePdfUrl, login, logout, updateFeeType } from './api';
import type { AuthUser, FeeType, StudentRecord } from './types';

type Screen = 'feeTypes' | 'generateBill' | 'emiPlans' | 'invoice';

const screens: Array<{ id: Screen; label: string; icon: string; title: string; eyebrow: string; description: string }> = [
  {
    id: 'feeTypes',
    label: 'Fee Types',
    icon: 'payments',
    title: 'Fee Types',
    eyebrow: 'Finance / Fee Types',
    description: 'Define and manage academic and service fee structures.'
  },
  {
    id: 'generateBill',
    label: 'Generate Bill',
    icon: 'receipt_long',
    title: 'Generate Bill',
    eyebrow: 'Finance / New Invoice',
    description: 'Create student bills from active fee types and due dates.'
  },
  {
    id: 'emiPlans',
    label: 'EMI Plans',
    icon: 'event_repeat',
    title: 'Create EMI Plan',
    eyebrow: 'Finance / Installments',
    description: 'Convert outstanding bills into manageable installment schedules.'
  },
  {
    id: 'invoice',
    label: 'Invoices',
    icon: 'description',
    title: 'Invoice PDF Preview',
    eyebrow: 'Finance / Invoice Preview',
    description: 'Preview, export, or print the latest generated invoice.'
  }
];

export function App() {
  const [screen, setScreen] = useState<Screen>('feeTypes');
  const [schoolCode, setSchoolCode] = useState('1');
  const [user, setUser] = useState<AuthUser | null>(null);
  const [checkingAuth, setCheckingAuth] = useState(true);
  const [students, setStudents] = useState<StudentRecord[]>([]);
  const [feeTypes, setFeeTypes] = useState<FeeType[]>([]);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');
  const [generatedBillId, setGeneratedBillId] = useState<number | null>(null);

  const activeScreen = screens.find((item) => item.id === screen) || screens[0];

  useEffect(() => {
    void restoreSession();
  }, []);

  useEffect(() => {
    if (user) {
      void loadData();
    }
  }, [schoolCode, user]);

  async function restoreSession() {
    const sessionUser = await fetchCurrentUser();
    if (sessionUser) {
      setUser(sessionUser);
      setSchoolCode(sessionUser.school_code);
    }
    setCheckingAuth(false);
  }

  async function loadData() {
    if (!user) return;
    setLoading(true);
    setError('');
    try {
      const [studentData, feeData] = await Promise.all([fetchStudents(schoolCode), fetchFeeTypes(schoolCode)]);
      setStudents(studentData);
      setFeeTypes(feeData);
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Failed to load data');
    } finally {
      setLoading(false);
    }
  }

  async function handleLogout() {
    await logout();
    setUser(null);
    setStudents([]);
    setFeeTypes([]);
    setGeneratedBillId(null);
    setScreen('feeTypes');
  }

  if (checkingAuth) {
    return (
      <div className="auth-page">
        <div className="card auth-card">
          <span className="material-symbols-outlined auth-icon">hourglass_top</span>
          <h1>Checking Session</h1>
          <p className="info">Preparing your finance workspace...</p>
        </div>
      </div>
    );
  }

  if (!user) {
    return (
      <LoginScreen
        onLoggedIn={(nextUser) => {
          setUser(nextUser);
          setSchoolCode(nextUser.school_code);
        }}
      />
    );
  }

  return (
    <div className="admin-layout">
      <aside className="sidebar">
        <div className="brand">
          <div className="brand-mark">
            <span className="material-symbols-outlined">school</span>
          </div>
          <div>
            <h1>Acme Academy</h1>
            <p>Financial Management</p>
          </div>
        </div>

        <nav className="side-nav" aria-label="Finance sections">
          {screens.map((item) => (
            <button
              key={item.id}
              type="button"
              className={screen === item.id ? 'active' : ''}
              onClick={() => setScreen(item.id)}
            >
              <span className="material-symbols-outlined">{item.icon}</span>
              <span>{item.label}</span>
            </button>
          ))}
        </nav>

      </aside>

      <div className="main-shell">
        <header className="topbar">
          <div>
            <p className="topbar-kicker">EduFinance Admin</p>
            <h2>{activeScreen.label}</h2>
          </div>
          <div className="topbar-actions">
            <label className="school-picker">
              <span className="material-symbols-outlined">corporate_fare</span>
              <select value={schoolCode} disabled onChange={(event) => setSchoolCode(event.target.value)}>
                <option value="1">School 1</option>
                <option value="2">School 2</option>
              </select>
            </label>
            <button type="button" className="logout-button" onClick={handleLogout}>
              <span className="material-symbols-outlined">logout</span>
              <span>Logout</span>
            </button>
          </div>
        </header>

        <main className="content">
          <PageHeader
            eyebrow={activeScreen.eyebrow}
            title={activeScreen.title}
            description={activeScreen.description}
            user={user}
          />

          {(loading || error) && (
            <div className="status-stack">
              {loading && <p className="status info">Loading finance data...</p>}
              {error && <p className="status error">{error}</p>}
            </div>
          )}

          {screen === 'feeTypes' && <FeeTypesScreen schoolCode={schoolCode} feeTypes={feeTypes} onChanged={loadData} />}
          {screen === 'generateBill' && (
            <GenerateBillScreen
              students={students}
              feeTypes={feeTypes}
              schoolCode={schoolCode}
              onCreated={(billId) => {
                setGeneratedBillId(billId);
                setScreen('invoice');
              }}
            />
          )}
          {screen === 'emiPlans' && <EmiScreen schoolCode={schoolCode} billId={generatedBillId} />}
          {screen === 'invoice' && <InvoiceScreen billId={generatedBillId} onGenerateBill={() => setScreen('generateBill')} />}
        </main>

      </div>
    </div>
  );
}

function PageHeader({ eyebrow, title, description, user }: { eyebrow: string; title: string; description: string; user: AuthUser }) {
  return (
    <section className="page-header">
      <div>
        <p className="breadcrumb">{eyebrow}</p>
        <h1>{title}</h1>
        <p>{description}</p>
      </div>
      <div className="user-card">
        <span className="material-symbols-outlined">verified_user</span>
        <div>
          <span>Logged in as</span>
          <strong>{user.full_name || user.username}</strong>
        </div>
      </div>
    </section>
  );
}

function LoginScreen({ onLoggedIn }: { onLoggedIn: (user: AuthUser) => void }) {
  const [schoolCode, setSchoolCode] = useState('1');
  const [username, setUsername] = useState('');
  const [password, setPassword] = useState('');
  const [error, setError] = useState('');
  const [submitting, setSubmitting] = useState(false);

  async function submit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    setSubmitting(true);
    setError('');
    try {
      const user = await login(username, password, schoolCode);
      onLoggedIn(user);
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Login failed');
    } finally {
      setSubmitting(false);
    }
  }

  return (
    <div className="auth-page">
      <form className="card auth-card" onSubmit={submit}>
        <div className="brand-mark auth-mark">
          <span className="material-symbols-outlined">school</span>
        </div>
        <div>
          <p className="breadcrumb">EduFinance Admin</p>
          <h1>Finance Login</h1>
          <p className="info">Sign in to manage billing, invoices, and EMI plans.</p>
        </div>
        <label className="field">
          <span>Institution</span>
          <select value={schoolCode} onChange={(event) => setSchoolCode(event.target.value)}>
            <option value="1">School 1</option>
            <option value="2">School 2</option>
          </select>
        </label>
        <label className="field">
          <span>Username</span>
          <input autoComplete="username" placeholder="Enter username" value={username} onChange={(event) => setUsername(event.target.value)} />
        </label>
        <label className="field">
          <span>Password</span>
          <input autoComplete="current-password" placeholder="Enter password" type="password" value={password} onChange={(event) => setPassword(event.target.value)} />
        </label>
        <button className="primary-action" type="submit" disabled={submitting}>
          {submitting ? 'Logging in...' : 'Login'}
        </button>
        {error && <p className="status error">{error}</p>}
      </form>
    </div>
  );
}

function FeeTypesScreen({ schoolCode, feeTypes, onChanged }: { schoolCode: string; feeTypes: FeeType[]; onChanged: () => Promise<void> }) {
  const [name, setName] = useState('');
  const [category, setCategory] = useState('');
  const [defaultAmount, setDefaultAmount] = useState('');
  const [recurrence, setRecurrence] = useState<FeeType['recurrence']>('one_time');
  const activeFeeTypes = feeTypes.filter((feeType) => feeType.is_active);
  const monthlyTotal = activeFeeTypes.reduce((total, feeType) => total + Number(feeType.default_amount || 0), 0);

  async function saveFeeType(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    await createFeeType({ name, category, default_amount: Number(defaultAmount), recurrence }, schoolCode);
    setName('');
    setCategory('');
    setDefaultAmount('');
    setRecurrence('one_time');
    await onChanged();
  }

  return (
    <div className="screen-grid fee-types-grid">
      <section className="card focus-card">
        <CardTitle icon="add_card" title="Add Fee Type" />
        <form className="form-grid" onSubmit={saveFeeType}>
          <label className="field">
            <span>Fee Name</span>
            <input required placeholder="e.g. Tuition Fee" value={name} onChange={(event) => setName(event.target.value)} />
          </label>
          <label className="field">
            <span>Category</span>
            <input required placeholder="e.g. Academic" value={category} onChange={(event) => setCategory(event.target.value)} />
          </label>
          <label className="field">
            <span>Default Amount</span>
            <input required min="0" placeholder="0.00" type="number" value={defaultAmount} onChange={(event) => setDefaultAmount(event.target.value)} />
          </label>
          <label className="field">
            <span>Frequency</span>
            <select value={recurrence} onChange={(event) => setRecurrence(event.target.value as FeeType['recurrence'])}>
              <option value="one_time">One time</option>
              <option value="monthly">Monthly</option>
              <option value="termly">Termly</option>
              <option value="yearly">Yearly</option>
            </select>
          </label>
          <button className="primary-action wide" type="submit">Save Fee Type</button>
        </form>
      </section>

      <aside className="stack">
        <section className="metric-card">
          <span className="material-symbols-outlined watermark">monitoring</span>
          <p>Total Active Fee Value</p>
          <h2>{formatCurrency(monthlyTotal)}</h2>
          <span className="metric-note">
            <span className="material-symbols-outlined">trending_up</span>
            {activeFeeTypes.length} active fee structures
          </span>
        </section>
        <section className="tip-card">
          <CardTitle icon="lightbulb" title="Quick Tip" compact />
          <p>Group fees into Academic, Transport, and Activities to make reports easier to reconcile.</p>
        </section>
      </aside>

      <section className="card table-card full-span">
        <div className="table-toolbar">
          <CardTitle icon="list_alt" title="Existing Fee Types" compact />
            <div className="toolbar-actions">
            <div className="search-box">
              <span className="material-symbols-outlined">search</span>
              <input aria-label="Search fees" placeholder="Search fees..." />
            </div>
          </div>
        </div>
        <div className="table-wrap">
          <table>
            <thead>
              <tr>
                <th>Fee Name</th>
                <th>Category</th>
                <th>Frequency</th>
                <th>Amount</th>
                <th>Status</th>
                <th aria-label="Actions" />
              </tr>
            </thead>
            <tbody>
              {feeTypes.length === 0 ? (
                <tr>
                  <td colSpan={6} className="empty-cell">No fee types found for this school.</td>
                </tr>
              ) : (
                feeTypes.map((feeType) => (
                  <tr key={feeType.id}>
                    <td><strong>{feeType.name}</strong></td>
                    <td>{feeType.category}</td>
                    <td><span className="chip">{formatRecurrence(feeType.recurrence)}</span></td>
                    <td>{formatCurrency(feeType.default_amount)}</td>
                    <td><StatusBadge active={Boolean(feeType.is_active)} /></td>
                    <td className="row-actions">
                      <button
                        type="button"
                        className="text-action"
                        onClick={async () => {
                          await updateFeeType({ ...feeType, is_active: feeType.is_active ? 0 : 1 }, schoolCode);
                          await onChanged();
                        }}
                      >
                        {feeType.is_active ? 'Disable' : 'Enable'}
                      </button>
                    </td>
                  </tr>
                ))
              )}
            </tbody>
          </table>
        </div>
        <div className="table-footer">
          <span>Showing {feeTypes.length} fee types</span>
          <span>{activeFeeTypes.length} active</span>
        </div>
      </section>
    </div>
  );
}

function GenerateBillScreen({
  students,
  feeTypes,
  schoolCode,
  onCreated
}: {
  students: StudentRecord[];
  feeTypes: FeeType[];
  schoolCode: string;
  onCreated: (billId: number) => void;
}) {
  const [selectedKey, setSelectedKey] = useState('');
  const [enrolledKey, setEnrolledKey] = useState('');
  const [admissionsKey, setAdmissionsKey] = useState('');
  const [studentSearch, setStudentSearch] = useState('');
  const [dueDate, setDueDate] = useState('');
  const [selectedFeeType, setSelectedFeeType] = useState('');
  const [extraAmount, setExtraAmount] = useState('');
  const [billNo, setBillNo] = useState('');

  const selectedStudent = useMemo(
    () => students.find((student) => `${student.source}:${student.source_student_id}` === selectedKey) || null,
    [students, selectedKey]
  );
  const pickedFeeType = feeTypes.find((feeType) => String(feeType.id) === selectedFeeType) || null;
  const activeFeeTypes = feeTypes.filter((feeType) => feeType.is_active);

  const searchLower = studentSearch.toLowerCase();
  const enrolledStudents = useMemo(
    () => students.filter((s) => s.source === 'anubhava' && (searchLower === '' || s.student_name.toLowerCase().includes(searchLower))),
    [students, searchLower]
  );
  const admissionStudents = useMemo(
    () => students.filter((s) => s.source === 'central' && (searchLower === '' || s.student_name.toLowerCase().includes(searchLower))),
    [students, searchLower]
  );

  function handleEnrolledChange(value: string) {
    setEnrolledKey(value);
    setAdmissionsKey('');
    setSelectedKey(value);
  }

  function handleAdmissionsChange(value: string) {
    setAdmissionsKey(value);
    setEnrolledKey('');
    setSelectedKey(value);
  }

  async function createBill(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    if (!selectedStudent || !pickedFeeType || !dueDate) return;
    const amount = extraAmount ? Number(extraAmount) : Number(pickedFeeType.default_amount);
    const res = await generateBill({
      student_source: selectedStudent.source,
      source_student_id: selectedStudent.source_student_id,
      student_name: selectedStudent.student_name,
      due_date: dueDate,
      items: [{ fee_type_id: pickedFeeType.id, description: pickedFeeType.name, quantity: 1, amount }]
    }, schoolCode);
    setBillNo(res.bill_no);
    onCreated(res.bill_id);
  }

  return (
    <div className="screen-grid bill-grid">
      <section className="card focus-card">
        <div className="section-header">
          <CardTitle icon="receipt_long" title="Generate Bill" compact />
          <span className="pill">New Invoice</span>
        </div>
        <form className="form-grid" onSubmit={createBill}>
          <div className="field student-search-field">
            <span>Search Students</span>
            <div className="search-box">
              <span className="material-symbols-outlined">search</span>
              <input
                aria-label="Search students"
                placeholder="Type a name to filter..."
                value={studentSearch}
                onChange={(event) => setStudentSearch(event.target.value)}
              />
            </div>
          </div>
          <label className="field">
            <span>Active Enrolled Students</span>
            <select value={enrolledKey} onChange={(event) => handleEnrolledChange(event.target.value)}>
              <option value="">Choose enrolled student...</option>
              {enrolledStudents.map((student) => (
                <option key={`${student.source}-${student.source_student_id}`} value={`${student.source}:${student.source_student_id}`}>
                  {student.student_name} ({student.class_name || '—'}, {student.lifecycle_status})
                </option>
              ))}
            </select>
          </label>
          <label className="field">
            <span>Admissions Portal Students</span>
            <select value={admissionsKey} onChange={(event) => handleAdmissionsChange(event.target.value)}>
              <option value="">Choose admissions student...</option>
              {admissionStudents.map((student) => (
                <option key={`${student.source}-${student.source_student_id}`} value={`${student.source}:${student.source_student_id}`}>
                  {student.student_name} ({student.class_name || '—'}, {student.lifecycle_status})
                </option>
              ))}
            </select>
          </label>
          <label className="field">
            <span>Due Date</span>
            <input required type="date" value={dueDate} onChange={(event) => setDueDate(event.target.value)} />
          </label>
          <label className="field">
            <span>Select Fee Type</span>
            <select required value={selectedFeeType} onChange={(event) => setSelectedFeeType(event.target.value)}>
              <option value="">Choose category...</option>
              {activeFeeTypes.map((feeType) => <option key={feeType.id} value={feeType.id}>{feeType.name}</option>)}
            </select>
          </label>
          <label className="field">
            <span>Custom Amount (optional)</span>
            <input min="0" type="number" placeholder={pickedFeeType ? String(pickedFeeType.default_amount) : 'Enter amount...'} value={extraAmount} onChange={(event) => setExtraAmount(event.target.value)} />
          </label>
          <button className="primary-action wide" type="submit">
            <span className="material-symbols-outlined">add_card</span>
            Create Bill
          </button>
        </form>
        {billNo && <p className="status success">Bill created: {billNo}</p>}
      </section>

      <aside className="stack">
        <section className="card activity-card">
          <CardTitle icon="history" title="Recent Bills" compact />
          {billNo ? (
            <div className="activity-row">
              <div>
                <strong>{billNo}</strong>
                <span>{selectedStudent?.student_name || 'Student'} • {pickedFeeType?.name || 'Fee'}</span>
              </div>
              <strong>{formatCurrency(extraAmount ? Number(extraAmount) : Number(pickedFeeType?.default_amount || 0))}</strong>
            </div>
          ) : (
            <p className="muted">Create a bill to see it appear here and unlock invoice download.</p>
          )}
        </section>
      </aside>

      <section className="card full-span feature-panel">
        <div className="feature-visual">
          <span className="material-symbols-outlined">auto_awesome</span>
        </div>
        <div>
          <h3>Automated Fee Management</h3>
          <p>Use active fee types as billing templates, then override the amount only when a student needs a custom bill.</p>
          <div className="feature-tags">
            <span>Secure Billing</span>
            <span>Fast Reconciliation</span>
          </div>
        </div>
      </section>
    </div>
  );
}

function EmiScreen({ schoolCode, billId }: { schoolCode: string; billId: number | null }) {
  const [count, setCount] = useState(3);
  const [amount, setAmount] = useState('');
  const [startDate, setStartDate] = useState('');
  const [saved, setSaved] = useState(false);
  const installments = useMemo(
    () => Array.from({ length: Math.max(count, 0) }).map((_, index) => ({ due_date: addMonths(startDate, index), amount: Number(amount || 0) })),
    [count, startDate, amount]
  );

  async function saveEmiPlan(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    if (!billId) return;
    await createEmiPlan(billId, installments, schoolCode);
    setSaved(true);
  }

  return (
    <div className="screen-grid">
      <section className="card focus-card full-span">
        <CardTitle icon="event_repeat" title="Create EMI Plan" />
        {!billId && (
          <div className="notice">
            <span className="material-symbols-outlined">info</span>
            <div>
              <strong>Generate a bill first to create EMI plan.</strong>
              <p>Only active bills with outstanding amounts can be converted to installments.</p>
            </div>
          </div>
        )}
        <form className="form-grid three-column" onSubmit={saveEmiPlan}>
          <label className="field">
            <span>Number of Installments</span>
            <input min={1} type="number" value={count} onChange={(event) => setCount(Number(event.target.value))} />
          </label>
          <label className="field">
            <span>Installment Amount</span>
            <input min="0" required={Boolean(billId)} type="number" placeholder="Enter amount" value={amount} onChange={(event) => setAmount(event.target.value)} />
          </label>
          <label className="field">
            <span>Start Date</span>
            <input required={Boolean(billId)} type="date" value={startDate} onChange={(event) => setStartDate(event.target.value)} />
          </label>
          <button className="primary-action wide" type="submit" disabled={!billId}>
            Save EMI Plan
            <span className="material-symbols-outlined">arrow_forward_ios</span>
          </button>
        </form>
        {saved && <p className="status success">EMI plan saved for bill #{billId}.</p>}
      </section>

    </div>
  );
}

function InvoiceScreen({ billId, onGenerateBill }: { billId: number | null; onGenerateBill: () => void }) {
  const pdfUrl = billId ? invoicePdfUrl(billId) : '';

  return (
    <div className="screen-grid invoice-grid">
      <section className="card invoice-preview">
        <div className="table-toolbar">
          <CardTitle icon="picture_as_pdf" title="Invoice PDF Preview" compact />
          <div className="toolbar-actions">
            {billId ? (
              <a className="primary-action small" href={pdfUrl}>
                <span className="material-symbols-outlined">download</span>
                Export
              </a>
            ) : (
              <button className="primary-action small" type="button" disabled>
                <span className="material-symbols-outlined">download</span>
                Export
              </button>
            )}
          </div>
        </div>

        {billId ? (
          <div className="invoice-ready">
            <span className="material-symbols-outlined">task_alt</span>
            <h3>Invoice Ready</h3>
            <p>Bill #{billId} is ready to download as a PDF document.</p>
            <a className="primary-action" href={pdfUrl}>Download Invoice PDF</a>
          </div>
        ) : (
          <div className="invoice-empty">
            <div className="document-ghost">
              <span />
              <span />
              <span />
              <i />
            </div>
            <h3>No Invoice Generated</h3>
            <p>Generate bill first to see a preview of the student invoice. You can then download or print the PDF document.</p>
            <button className="dark-action" type="button" onClick={onGenerateBill}>Go to Generate Bill</button>
          </div>
        )}

        <div className="invoice-footer">
          <span><span className="material-symbols-outlined">verified_user</span> Academic financial format</span>
          <span>System Status: <strong>Operational</strong></span>
        </div>
      </section>

    </div>
  );
}

function CardTitle({ icon, title, compact = false }: { icon: string; title: string; compact?: boolean }) {
  return (
    <div className={compact ? 'card-title compact' : 'card-title'}>
      <span className="material-symbols-outlined">{icon}</span>
      <h2>{title}</h2>
    </div>
  );
}

function StatusBadge({ active }: { active: boolean }) {
  return (
    <span className={active ? 'status-badge active' : 'status-badge'}>
      <i />
      {active ? 'Active' : 'Inactive'}
    </span>
  );
}

function formatCurrency(value: number | string): string {
  return new Intl.NumberFormat('en-IN', {
    style: 'currency',
    currency: 'INR',
    maximumFractionDigits: 2
  }).format(Number(value || 0));
}

function formatRecurrence(recurrence: FeeType['recurrence']): string {
  const labels: Record<FeeType['recurrence'], string> = {
    one_time: 'One time',
    monthly: 'Monthly',
    termly: 'Termly',
    yearly: 'Yearly'
  };
  return labels[recurrence];
}

function addMonths(dateValue: string, monthsToAdd: number): string {
  if (!dateValue) return '';
  const date = new Date(dateValue);
  date.setMonth(date.getMonth() + monthsToAdd);
  return date.toISOString().split('T')[0];
}
