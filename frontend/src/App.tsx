import { useEffect, useMemo, useState } from 'react';
import { createEmiPlan, createFeeType, fetchFeeTypes, fetchStudents, generateBill, invoicePdfUrl, updateFeeType } from './api';
import type { FeeType, StudentRecord } from './types';

type Screen = 'feeTypes' | 'generateBill' | 'emiPlans' | 'invoice';

export function App() {
  const [screen, setScreen] = useState<Screen>('feeTypes');
  const [schoolCode, setSchoolCode] = useState('1');
  const [students, setStudents] = useState<StudentRecord[]>([]);
  const [feeTypes, setFeeTypes] = useState<FeeType[]>([]);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');
  const [generatedBillId, setGeneratedBillId] = useState<number | null>(null);

  useEffect(() => {
    void loadData();
  }, [schoolCode]);

  async function loadData() {
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

  return (
    <div className="app">
      <header className="header">
        <h1>Finance</h1>
        <select value={schoolCode} onChange={(e) => setSchoolCode(e.target.value)}>
          <option value="1">School 1</option>
          <option value="2">School 2</option>
        </select>
      </header>
      <nav className="tabs">
        <button className={screen === 'feeTypes' ? 'active' : ''} onClick={() => setScreen('feeTypes')}>Fee Types</button>
        <button className={screen === 'generateBill' ? 'active' : ''} onClick={() => setScreen('generateBill')}>Generate Bill</button>
        <button className={screen === 'emiPlans' ? 'active' : ''} onClick={() => setScreen('emiPlans')}>EMI Plans</button>
        <button className={screen === 'invoice' ? 'active' : ''} onClick={() => setScreen('invoice')}>Invoice PDF</button>
      </nav>
      {loading && <p className="info">Loading...</p>}
      {error && <p className="error">{error}</p>}
      {screen === 'feeTypes' && <FeeTypesScreen schoolCode={schoolCode} feeTypes={feeTypes} onChanged={loadData} />}
      {screen === 'generateBill' && <GenerateBillScreen students={students} feeTypes={feeTypes} schoolCode={schoolCode} onCreated={setGeneratedBillId} />}
      {screen === 'emiPlans' && <EmiScreen schoolCode={schoolCode} billId={generatedBillId} />}
      {screen === 'invoice' && <InvoiceScreen billId={generatedBillId} />}
    </div>
  );
}

function FeeTypesScreen({ schoolCode, feeTypes, onChanged }: { schoolCode: string; feeTypes: FeeType[]; onChanged: () => Promise<void> }) {
  const [name, setName] = useState('');
  const [category, setCategory] = useState('');
  const [defaultAmount, setDefaultAmount] = useState('');
  const [recurrence, setRecurrence] = useState<FeeType['recurrence']>('one_time');

  return (
    <section className="card">
      <h2>Add Fee Type</h2>
      <div className="grid">
        <input placeholder="Fee name" value={name} onChange={(e) => setName(e.target.value)} />
        <input placeholder="Category" value={category} onChange={(e) => setCategory(e.target.value)} />
        <input placeholder="Default amount" type="number" value={defaultAmount} onChange={(e) => setDefaultAmount(e.target.value)} />
        <select value={recurrence} onChange={(e) => setRecurrence(e.target.value as FeeType['recurrence'])}>
          <option value="one_time">One time</option>
          <option value="monthly">Monthly</option>
          <option value="termly">Termly</option>
          <option value="yearly">Yearly</option>
        </select>
      </div>
      <button
        onClick={async () => {
          await createFeeType({ name, category, default_amount: Number(defaultAmount), recurrence }, schoolCode);
          setName('');
          setCategory('');
          setDefaultAmount('');
          await onChanged();
        }}
      >
        Save Fee Type
      </button>
      <h3>Existing</h3>
      {feeTypes.map((ft) => (
        <div key={ft.id} className="row">
          <div>
            <strong>{ft.name}</strong> - Rs.{Number(ft.default_amount).toFixed(2)} ({ft.recurrence})
          </div>
          <button onClick={async () => { await updateFeeType({ ...ft, is_active: ft.is_active ? 0 : 1 }, schoolCode); await onChanged(); }}>
            {ft.is_active ? 'Disable' : 'Enable'}
          </button>
        </div>
      ))}
    </section>
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
  const [dueDate, setDueDate] = useState('');
  const [selectedFeeType, setSelectedFeeType] = useState('');
  const [extraAmount, setExtraAmount] = useState('');
  const [billNo, setBillNo] = useState('');

  const selectedStudent = useMemo(
    () => students.find((s) => `${s.source}:${s.source_student_id}` === selectedKey) || null,
    [students, selectedKey]
  );
  const pickedFeeType = feeTypes.find((f) => String(f.id) === selectedFeeType) || null;

  return (
    <section className="card">
      <h2>Generate Bill</h2>
      <div className="grid">
        <select value={selectedKey} onChange={(e) => setSelectedKey(e.target.value)}>
          <option value="">Select student</option>
          {students.map((s) => (
            <option key={`${s.source}-${s.source_student_id}`} value={`${s.source}:${s.source_student_id}`}>
              {s.student_name} ({s.source}, {s.lifecycle_status})
            </option>
          ))}
        </select>
        <input type="date" value={dueDate} onChange={(e) => setDueDate(e.target.value)} />
        <select value={selectedFeeType} onChange={(e) => setSelectedFeeType(e.target.value)}>
          <option value="">Select fee type</option>
          {feeTypes.filter((f) => f.is_active).map((f) => <option key={f.id} value={f.id}>{f.name}</option>)}
        </select>
        <input type="number" placeholder="Custom amount (optional)" value={extraAmount} onChange={(e) => setExtraAmount(e.target.value)} />
      </div>
      <button
        onClick={async () => {
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
        }}
      >
        Create Bill
      </button>
      {billNo && <p className="success">Bill created: {billNo}</p>}
    </section>
  );
}

function EmiScreen({ schoolCode, billId }: { schoolCode: string; billId: number | null }) {
  const [count, setCount] = useState(3);
  const [amount, setAmount] = useState('');
  const [startDate, setStartDate] = useState('');
  const installments = useMemo(
    () => Array.from({ length: count }).map((_, i) => ({ due_date: addMonths(startDate, i), amount: Number(amount || 0) })),
    [count, startDate, amount]
  );

  return (
    <section className="card">
      <h2>Create EMI Plan</h2>
      {!billId && <p className="info">Generate a bill first to create EMI plan.</p>}
      <div className="grid">
        <input type="number" min={1} value={count} onChange={(e) => setCount(Number(e.target.value))} />
        <input type="number" placeholder="Installment amount" value={amount} onChange={(e) => setAmount(e.target.value)} />
        <input type="date" value={startDate} onChange={(e) => setStartDate(e.target.value)} />
      </div>
      <button
        disabled={!billId}
        onClick={async () => {
          if (!billId) return;
          await createEmiPlan(billId, installments, schoolCode);
          alert('EMI plan saved');
        }}
      >
        Save EMI Plan
      </button>
    </section>
  );
}

function InvoiceScreen({ billId }: { billId: number | null }) {
  return (
    <section className="card">
      <h2>Invoice PDF</h2>
      {!billId ? (
        <p className="info">Generate bill first.</p>
      ) : (
        <a className="button-link" href={invoicePdfUrl(billId)}>
          Download Invoice PDF
        </a>
      )}
    </section>
  );
}

function addMonths(dateValue: string, monthsToAdd: number): string {
  if (!dateValue) return '';
  const date = new Date(dateValue);
  date.setMonth(date.getMonth() + monthsToAdd);
  return date.toISOString().split('T')[0];
}
