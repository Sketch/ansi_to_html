These files provide a solution to an often-posed problem: How does one convert ANSI/XTerm from a MUSH into HTML?

The solution is easy. There is only one function to call.
example.php provides an example of how this project is intended to be used.
The code only alters escape codes that match the regexp \e\[[0-9;]+m, transforming them to HTML span tags, and ignoring anything else.
More technical details are provided in the code.

