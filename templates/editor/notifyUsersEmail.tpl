{translate key="email.multipart"}

--{$mimeBoundary}
Content-Type: text/plain; charset=us-ascii
Content-Transfer-Encoding: 7bit

{$body}

--{$mimeBoundary}
Content-Type: text/html; charset=us-ascii
Content-Transfer-Encoding: 7bit

<html>
	<body>
		<pre>
			{$body}
		</pre>
	</body>
</html>

--{$mimeBoundary}--
