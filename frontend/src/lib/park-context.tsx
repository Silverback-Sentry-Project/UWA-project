import { createContext, useContext, useEffect, useState, type ReactNode } from "react";

interface ParkContextValue {
  selectedParkId: string | null; // null means "All Parks"
  setSelectedParkId: (id: string | null) => void;
}

const ParkContext = createContext<ParkContextValue | null>(null);

const STORAGE_KEY = "wildwatch_selected_park";

export function ParkProvider({ children }: { children: ReactNode }) {
  const [selectedParkId, setSelectedParkIdState] = useState<string | null>(null);

  useEffect(() => {
    const saved = localStorage.getItem(STORAGE_KEY);
    if (saved) setSelectedParkIdState(saved);
  }, []);

  const setSelectedParkId = (id: string | null) => {
    setSelectedParkIdState(id);
    if (id) localStorage.setItem(STORAGE_KEY, id);
    else localStorage.removeItem(STORAGE_KEY);
  };

  return (
    <ParkContext.Provider value={{ selectedParkId, setSelectedParkId }}>
      {children}
    </ParkContext.Provider>
  );
}

export function usePark() {
  const ctx = useContext(ParkContext);
  if (!ctx) throw new Error("usePark must be used within a ParkProvider");
  return ctx;
}
