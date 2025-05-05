import tkinter as tk
from tkinter import filedialog, ttk, messagebox, scrolledtext
import pandas as pd
import mysql.connector
from sqlalchemy import create_engine
import os
import sys
import threading
import io
from datetime import datetime

class RedirectText:
    def __init__(self, text_widget):
        self.output = text_widget

    def write(self, string):
        self.output.configure(state='normal')
        self.output.insert(tk.END, string)
        self.output.see(tk.END)
        self.output.configure(state='disabled')
    
    def flush(self):
        pass

class ExcelToMySQLApp:
    def __init__(self, root):
        self.root = root
        self.root.title("Excel/CSV to MySQL Converter")
        self.root.geometry("900x700")
        self.root.configure(padx=10, pady=10)
        
        # Variables
        self.file_path = tk.StringVar()
        self.host = tk.StringVar(value="localhost")
        self.user = tk.StringVar(value="hamza")
        self.password = tk.StringVar(value="")
        self.database = tk.StringVar(value="jgr")
        self.table_name = tk.StringVar(value="barcodes")
        self.if_exists = tk.StringVar(value="replace")
        self.date_format = tk.StringVar(value="%d/%m/%Y")  # Added date format variable
        self.target_date_format = "%Y-%m-%d %H:%M:%S"  # MySQL standard datetime format
        
        # Setup UI
        self.setup_ui()
        
        # DataFrame
        self.df = None
    
    def setup_ui(self):
        # Create main frame
        main_frame = ttk.Frame(self.root)
        main_frame.pack(fill=tk.BOTH, expand=True)
        
        # Create a notebook (tabs)
        notebook = ttk.Notebook(main_frame)
        notebook.pack(fill=tk.BOTH, expand=True, padx=5, pady=5)
        
        # Create tabs
        self.setup_tab = ttk.Frame(notebook)
        self.preview_tab = ttk.Frame(notebook)
        self.log_tab = ttk.Frame(notebook)
        
        notebook.add(self.setup_tab, text="Setup")
        notebook.add(self.preview_tab, text="Data Preview")
        notebook.add(self.log_tab, text="Log")
        
        # Setup Tab
        self.create_setup_tab()
        
        # Preview Tab
        self.create_preview_tab()
        
        # Log Tab
        self.create_log_tab()
        
        # Status bar
        self.status_var = tk.StringVar()
        self.status_var.set("Ready")
        status_bar = ttk.Label(self.root, textvariable=self.status_var, relief=tk.SUNKEN, anchor=tk.W)
        status_bar.pack(side=tk.BOTTOM, fill=tk.X)
    
    def create_setup_tab(self):
        # File selection
        file_frame = ttk.LabelFrame(self.setup_tab, text="File Selection")
        file_frame.pack(fill=tk.X, padx=5, pady=5)
        
        ttk.Label(file_frame, text="File Path:").grid(row=0, column=0, padx=5, pady=5, sticky=tk.W)
        ttk.Entry(file_frame, textvariable=self.file_path, width=60).grid(row=0, column=1, padx=5, pady=5, sticky=tk.W)
        ttk.Button(file_frame, text="Browse...", command=self.browse_file).grid(row=0, column=2, padx=5, pady=5)
        
        # Date format selection
        date_frame = ttk.LabelFrame(self.setup_tab, text="Date Format")
        date_frame.pack(fill=tk.X, padx=5, pady=5)
        
        ttk.Label(date_frame, text="Source Date Format:").grid(row=0, column=0, padx=5, pady=5, sticky=tk.W)
        date_combo = ttk.Combobox(date_frame, textvariable=self.date_format, 
                                 values=["%d/%m/%Y", "%m/%d/%Y", "%Y-%m-%d", "%Y/%m/%d", 
                                         "%d/%m/%Y %H:%M:%S", "%m/%d/%Y %H:%M:%S", "%Y-%m-%d %H:%M:%S"])
        date_combo.grid(row=0, column=1, padx=5, pady=5, sticky=tk.W)
        ttk.Label(date_frame, text="Examples: 31/12/2023, 12/31/2023, 2023-12-31, 2023-12-31 18:09:24").grid(
            row=1, column=0, columnspan=3, padx=5, pady=5, sticky=tk.W)
        
        ttk.Label(date_frame, text="Note: Dates will be imported as YYYY-MM-DD HH:MM:SS in MySQL").grid(
            row=2, column=0, columnspan=3, padx=5, pady=5, sticky=tk.W)
        
        # Database connection
        db_frame = ttk.LabelFrame(self.setup_tab, text="Database Connection")
        db_frame.pack(fill=tk.X, padx=5, pady=5)
        
        ttk.Label(db_frame, text="Host:").grid(row=0, column=0, padx=5, pady=5, sticky=tk.W)
        ttk.Entry(db_frame, textvariable=self.host).grid(row=0, column=1, padx=5, pady=5, sticky=tk.W)
        
        ttk.Label(db_frame, text="Username:").grid(row=1, column=0, padx=5, pady=5, sticky=tk.W)
        ttk.Entry(db_frame, textvariable=self.user).grid(row=1, column=1, padx=5, pady=5, sticky=tk.W)
        
        ttk.Label(db_frame, text="Password:").grid(row=2, column=0, padx=5, pady=5, sticky=tk.W)
        ttk.Entry(db_frame, textvariable=self.password, show="*").grid(row=2, column=1, padx=5, pady=5, sticky=tk.W)
        
        ttk.Label(db_frame, text="Database:").grid(row=3, column=0, padx=5, pady=5, sticky=tk.W)
        ttk.Entry(db_frame, textvariable=self.database).grid(row=3, column=1, padx=5, pady=5, sticky=tk.W)
        
        ttk.Label(db_frame, text="Table Name:").grid(row=4, column=0, padx=5, pady=5, sticky=tk.W)
        ttk.Entry(db_frame, textvariable=self.table_name).grid(row=4, column=1, padx=5, pady=5, sticky=tk.W)
        
        ttk.Label(db_frame, text="If Table Exists:").grid(row=5, column=0, padx=5, pady=5, sticky=tk.W)
        ttk.Combobox(db_frame, textvariable=self.if_exists, values=["replace", "append", "fail"]).grid(row=5, column=1, padx=5, pady=5, sticky=tk.W)
        
        ttk.Button(db_frame, text="Test Connection", command=self.test_connection).grid(row=6, column=0, padx=5, pady=5)
        
        # Action buttons
        action_frame = ttk.Frame(self.setup_tab)
        action_frame.pack(fill=tk.X, padx=5, pady=15)
        
        ttk.Button(action_frame, text="Load File", command=self.load_file).pack(side=tk.LEFT, padx=5)
        self.import_button = ttk.Button(action_frame, text="Import to MySQL", command=self.import_to_mysql, state=tk.DISABLED)
        self.import_button.pack(side=tk.LEFT, padx=5)
        ttk.Button(action_frame, text="Save Settings", command=self.save_settings).pack(side=tk.LEFT, padx=5)
        ttk.Button(action_frame, text="Load Settings", command=self.load_settings).pack(side=tk.LEFT, padx=5)
        ttk.Button(action_frame, text="Clear Log", command=self.clear_log).pack(side=tk.RIGHT, padx=5)
        
    def create_preview_tab(self):
        preview_frame = ttk.Frame(self.preview_tab)
        preview_frame.pack(fill=tk.BOTH, expand=True, padx=5, pady=5)
        
        # Preview info
        info_frame = ttk.Frame(preview_frame)
        info_frame.pack(fill=tk.X, padx=5, pady=5)
        
        self.rows_label = ttk.Label(info_frame, text="Rows: 0")
        self.rows_label.pack(side=tk.LEFT, padx=5)
        
        self.cols_label = ttk.Label(info_frame, text="Columns: 0")
        self.cols_label.pack(side=tk.LEFT, padx=5)
        
        # Data preview table
        table_frame = ttk.Frame(preview_frame)
        table_frame.pack(fill=tk.BOTH, expand=True, padx=5, pady=5)
        
        # Create Treeview widget
        self.tree = ttk.Treeview(table_frame)
        self.tree.pack(side=tk.LEFT, fill=tk.BOTH, expand=True)
        
        # Add a scrollbar
        scrollbar = ttk.Scrollbar(table_frame, orient=tk.VERTICAL, command=self.tree.yview)
        scrollbar.pack(side=tk.RIGHT, fill=tk.Y)
        self.tree.configure(yscrollcommand=scrollbar.set)
        
        # Horizontal scrollbar
        h_scrollbar = ttk.Scrollbar(preview_frame, orient=tk.HORIZONTAL, command=self.tree.xview)
        h_scrollbar.pack(fill=tk.X)
        self.tree.configure(xscrollcommand=h_scrollbar.set)
    
    def create_log_tab(self):
        log_frame = ttk.Frame(self.log_tab)
        log_frame.pack(fill=tk.BOTH, expand=True, padx=5, pady=5)
        
        # Log text area
        self.log_text = scrolledtext.ScrolledText(log_frame, wrap=tk.WORD, state='disabled')
        self.log_text.pack(fill=tk.BOTH, expand=True)
        
        # Redirect stdout to the log text widget
        self.stdout_redirector = RedirectText(self.log_text)
        sys.stdout = self.stdout_redirector
    
    def browse_file(self):
        file_path = filedialog.askopenfilename(
            title="Select Excel or CSV File",
            filetypes=[
                ("Excel Files", "*.xlsx *.xls"),
                ("CSV Files", "*.csv"),
                ("All Files", "*.*")
            ]
        )
        if file_path:
            self.file_path.set(file_path)
    
    def test_connection(self):
        try:
            conn = mysql.connector.connect(
                host=self.host.get(),
                user=self.user.get(),
                password=self.password.get()
            )
            conn.close()
            messagebox.showinfo("Connection Test", "Connection to MySQL server successful!")
            self.log("Database connection test successful.")
        except Exception as e:
            messagebox.showerror("Connection Error", f"Failed to connect to MySQL server:\n{str(e)}")
            self.log(f"Connection test failed: {str(e)}")
    
    def load_file(self):
        file_path = self.file_path.get()
        if not file_path:
            messagebox.showwarning("No File Selected", "Please select a file first.")
            return
        
        try:
            _, file_extension = os.path.splitext(file_path)
            
            if file_extension.lower() in ['.xlsx', '.xls']:
                self.log(f"Reading Excel file: {file_path}")
                # Parse dates with parse_dates=True to handle date columns automatically
                self.df = pd.read_excel(file_path, parse_dates=True)
            elif file_extension.lower() == '.csv':
                self.log(f"Reading CSV file: {file_path}")
                # Parse dates in CSV
                self.df = pd.read_csv(file_path, parse_dates=True)
            else:
                messagebox.showerror("Unsupported Format", f"Unsupported file format: {file_extension}")
                return
            
            # Identify date columns for manual processing
            self.identify_date_columns()
            
            self.update_preview()
            self.import_button.config(state=tk.NORMAL)
            self.log("File loaded successfully.")
            self.status_var.set(f"Loaded: {os.path.basename(file_path)}")
        except Exception as e:
            messagebox.showerror("Error", f"Failed to load file:\n{str(e)}")
            self.log(f"Error loading file: {str(e)}")
    
    def identify_date_columns(self):
        """Identify and properly convert date columns"""
        if self.df is None:
            return
            
        # Look for potential date columns with string type
        for col in self.df.columns:
            # If column name suggests it might be a date (like 'last_update')
            if 'date' in col.lower() or 'time' in col.lower() or 'update' in col.lower():
                if self.df[col].dtype == 'object':  # String columns
                    try:
                        # Try to convert using the selected date format
                        self.log(f"Converting column '{col}' to datetime using format {self.date_format.get()}")
                        
                        # First try explicit format conversion
                        try:
                            self.df[col] = pd.to_datetime(self.df[col], format=self.date_format.get(), errors='raise')
                            self.log(f"Successfully converted '{col}' using format {self.date_format.get()}")
                        except:
                            # If explicit format fails, try auto-detection as fallback
                            self.log(f"Explicit format failed, trying automatic date detection for '{col}'")
                            self.df[col] = pd.to_datetime(self.df[col], errors='coerce')
                        
                        # Check if conversion was successful
                        null_count = self.df[col].isna().sum()
                        if null_count > 0:
                            self.log(f"Warning: {null_count} values in '{col}' could not be converted to dates.")
                        else:
                            self.log(f"Successfully converted all values in '{col}' to dates.")
                    except Exception as e:
                        self.log(f"Could not convert column '{col}' to datetime: {str(e)}")
    
    def update_preview(self):
        if self.df is None:
            return
        
        # Update info labels
        rows, cols = self.df.shape
        self.rows_label.config(text=f"Rows: {rows}")
        self.cols_label.config(text=f"Columns: {cols}")
        
        # Clear existing tree items
        for i in self.tree.get_children():
            self.tree.delete(i)
        
        # Configure columns
        self.tree["columns"] = list(self.df.columns)
        self.tree["show"] = "headings"
        
        for col in self.df.columns:
            self.tree.heading(col, text=col)
            self.tree.column(col, width=100)
        
        # Add data rows (limit to first 1000 rows for performance)
        display_rows = min(1000, len(self.df))
        for i in range(display_rows):
            values = list(self.df.iloc[i])
            # Convert any non-string values to strings, handle datetime objects specifically
            values = [x.strftime('%Y-%m-%d %H:%M:%S') if pd.api.types.is_datetime64_any_dtype(type(x)) else 
                     str(x) if not isinstance(x, str) else x for x in values]
            self.tree.insert("", "end", values=values)
        
        if display_rows < len(self.df):
            self.log(f"Preview showing first {display_rows} rows of {len(self.df)} total rows.")
    
    def import_to_mysql(self):
        if self.df is None:
            messagebox.showwarning("No Data", "Please load a file first.")
            return
        
        # Get database config
        db_config = {
            'host': self.host.get(),
            'user': self.user.get(),
            'password': self.password.get(),
            'database': self.database.get(),
            'table_name': self.table_name.get()
        }
        
        # Run in a separate thread to avoid freezing the UI
        self.status_var.set("Importing data to MySQL...")
        threading.Thread(target=self._do_import, args=(db_config,), daemon=True).start()
    
    def _do_import(self, db_config):
        try:
            self.log("\nConnecting to MySQL...")
            
            # Create database if it doesn't exist
            conn = mysql.connector.connect(
                host=db_config['host'],
                user=db_config['user'],
                password=db_config['password']
            )
            cursor = conn.cursor()
            
            cursor.execute(f"CREATE DATABASE IF NOT EXISTS {db_config['database']}")
            cursor.close()
            conn.close()
            
            # Create SQLAlchemy engine
            engine_url = f"mysql+mysqlconnector://{db_config['user']}:{db_config['password']}@{db_config['host']}/{db_config['database']}"
            engine = create_engine(engine_url)
            
            # Import dataframe to MySQL
            table_name = db_config['table_name']
            self.log(f"\nImporting data to MySQL table: {table_name}")
            
            # Convert column names to lowercase and replace spaces with underscores
            self.df.columns = [col.lower().replace(' ', '_') for col in self.df.columns]
            
            # Create a copy of the dataframe to avoid modifying the original
            df_to_import = self.df.copy()
            
            # Ensure all datetime columns are properly formatted for MySQL
            for col in df_to_import.columns:
                if pd.api.types.is_datetime64_any_dtype(df_to_import[col]):
                    self.log(f"Ensuring proper datetime format for column: {col}")
                    # Remove timezone info if present to avoid MySQL issues
                    if hasattr(df_to_import[col].dtype, 'tz') and df_to_import[col].dtype.tz is not None:
                        df_to_import[col] = df_to_import[col].dt.tz_localize(None)
                    
                    # Format dates as YYYY-MM-DD HH:MM:SS string to ensure consistent MySQL import
                    df_to_import[col] = df_to_import[col].dt.strftime(self.target_date_format)
            
            # Write to MySQL with custom data types for date columns
            dtype_dict = {}
            for col in df_to_import.columns:
                if pd.api.types.is_datetime64_any_dtype(df_to_import[col]):
                    # Set data type to DATETIME in MySQL
                    dtype_dict[col] = 'DATETIME'
            
            # Write to MySQL with specified data types
            try:
                df_to_import.to_sql(
                    name=table_name,
                    con=engine,
                    if_exists=self.if_exists.get(),
                    index=False,
                    chunksize=1000,
                    dtype=dtype_dict if dtype_dict else None
                )
            except Exception as sql_error:
                self.log(f"SQL import error: {str(sql_error)}")
                self.log("Attempting alternative import method...")
                
                # Create table structure first
                table_creation_query = self.generate_create_table_sql(df_to_import, table_name)
                with engine.connect() as connection:
                    if self.if_exists.get() == "replace":
                        connection.execute(f"DROP TABLE IF EXISTS {table_name}")
                    connection.execute(table_creation_query)
                
                # Then insert data
                df_to_import.to_sql(
                    name=table_name,
                    con=engine,
                    if_exists="append",
                    index=False,
                    chunksize=1000
                )
            
            self.log(f"Successfully imported {len(df_to_import)} rows to MySQL table '{table_name}'")
            
            # Update UI from the main thread
            self.root.after(0, lambda: self.status_var.set("Import completed successfully"))
            self.root.after(0, lambda: messagebox.showinfo("Success", f"Successfully imported {len(df_to_import)} rows to MySQL!"))
            
        except Exception as e:
            error_msg = str(e)
            self.log(f"Error: {error_msg}")
            self.root.after(0, lambda: self.status_var.set("Import failed"))
            self.root.after(0, lambda: messagebox.showerror("Import Error", f"Failed to import data:\n{error_msg}"))
    
    def save_settings(self):
        settings = {
            'host': self.host.get(),
            'user': self.user.get(),
            'password': self.password.get(),
            'database': self.database.get(),
            'table_name': self.table_name.get(),
            'if_exists': self.if_exists.get(),
            'date_format': self.date_format.get()  # Save date format too
        }
        
        file_path = filedialog.asksaveasfilename(
            title="Save Settings",
            filetypes=[("Text Files", "*.txt")],
            defaultextension=".txt"
        )
        
        if file_path:
            try:
                with open(file_path, 'w') as f:
                    for key, value in settings.items():
                        f.write(f"{key}={value}\n")
                self.log(f"Settings saved to {file_path}")
            except Exception as e:
                messagebox.showerror("Error", f"Failed to save settings:\n{str(e)}")
    
    def load_settings(self):
        file_path = filedialog.askopenfilename(
            title="Load Settings",
            filetypes=[("Text Files", "*.txt")],
            defaultextension=".txt"
        )
        
        if file_path:
            try:
                settings = {}
                with open(file_path, 'r') as f:
                    for line in f:
                        if '=' in line:
                            key, value = line.strip().split('=', 1)
                            settings[key] = value
                
                if 'host' in settings:
                    self.host.set(settings['host'])
                if 'user' in settings:
                    self.user.set(settings['user'])
                if 'password' in settings:
                    self.password.set(settings['password'])
                if 'database' in settings:
                    self.database.set(settings['database'])
                if 'table_name' in settings:
                    self.table_name.set(settings['table_name'])
                if 'if_exists' in settings:
                    self.if_exists.set(settings['if_exists'])
                if 'date_format' in settings:
                    self.date_format.set(settings['date_format'])
                
                self.log(f"Settings loaded from {file_path}")
            except Exception as e:
                messagebox.showerror("Error", f"Failed to load settings:\n{str(e)}")
    
    def log(self, message):
        timestamp = datetime.now().strftime("%Y-%m-%d %H:%M:%S")
        print(f"[{timestamp}] {message}")
    
    def clear_log(self):
        self.log_text.configure(state='normal')
        self.log_text.delete(1.0, tk.END)
        self.log_text.configure(state='disabled')
        self.log("Log cleared.")
    
    def generate_create_table_sql(self, df, table_name):
        """Generate SQL to create table with proper date columns"""
        columns = []
        for col in df.columns:
            data_type = "VARCHAR(255)"  # Default type
            
            # Check if column likely contains dates
            if 'date' in col.lower() or 'time' in col.lower() or 'update' in col.lower():
                data_type = "DATETIME"
            elif df[col].dtype.name == 'int64':
                data_type = "INT"
            elif df[col].dtype.name == 'float64':
                data_type = "FLOAT"
                
            columns.append(f"`{col}` {data_type}")
            
        column_defs = ", ".join(columns)
        create_sql = f"CREATE TABLE IF NOT EXISTS `{table_name}` ({column_defs})"
        
        self.log(f"Generated SQL: {create_sql}")
        return create_sql
        
    def on_closing(self):
        """Handle the closing event"""
        # Restore stdout
        sys.stdout = sys.__stdout__
        self.root.destroy()

def main():
    root = tk.Tk()
    app = ExcelToMySQLApp(root)
    root.protocol("WM_DELETE_WINDOW", app.on_closing)
    root.mainloop()

if __name__ == "__main__":
    main()